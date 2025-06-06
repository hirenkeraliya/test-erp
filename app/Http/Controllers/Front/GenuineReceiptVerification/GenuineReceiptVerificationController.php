<?php

namespace App\Http\Controllers\Front\GenuineReceiptVerification;

use App\Domains\GenuineReceiptVerification\DataObjects\GenuineReceiptVerificationData;
use App\Domains\GenuineReceiptVerification\DataObjects\GenuineReceiptVerificationMemberData;
use App\Domains\GenuineReceiptVerification\DataObjects\GenuineReceiptVerificationUpdateData;
use App\Domains\GenuineReceiptVerification\GenuineReceiptVerificationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SiteConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Throwable;

class GenuineReceiptVerificationController extends Controller
{
    public function index(): View
    {
        return view('front/verify-receipt/verify-receipt', [
            'themeColor' => $this->getThemeColor(),
            'cookieValue' => Cookie::get('verify-receipt'),
        ]);
    }

    public function store(GenuineReceiptVerificationData $genuineReceiptVerificationData): RedirectResponse|View
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getIdByOfflineSaleId($genuineReceiptVerificationData->receipt_number);

        if (! $sale instanceof Sale) {
            return to_route('front.genuine_receipt_verification.not_genuine_receipt', [
                'receipt_number' => $genuineReceiptVerificationData->receipt_number,
            ]);
        }

        if (! $sale->member_id) {
            return to_route('front.genuine_receipt_verification.genuine_receipt_member', [
                'receipt_number' => $genuineReceiptVerificationData->receipt_number,
            ]);
        }

        $member = $this->getMember($sale);

        try {
            $receiptVerificationData = $genuineReceiptVerificationData->toArray();
            $receiptVerificationData['name'] = $member?->first_name;
            $receiptVerificationData['email'] = $member?->email;
            $receiptVerificationData['mobile_number'] = $member?->mobile_number;
            $receiptVerificationData['sale_id'] = $sale->id;
            $receiptVerificationData['member_id'] = $member?->id;
            $receiptVerificationData['is_genuine'] = true;
            unset($receiptVerificationData['captcha']);

            $genuineReceiptVerificationQueries = resolve(GenuineReceiptVerificationQueries::class);

            $genuineReceiptVerification = $genuineReceiptVerificationQueries->addNew($receiptVerificationData);

            session()->put('genuineReceiptVerificationId', $genuineReceiptVerification->id);

            return to_route('front.genuine_receipt_verification.verified_receipt', [
                'offline_sale_id' => $sale->getOfflineSaleId(),
            ]);
        } catch (Throwable) {
            return back()->withErrors([
                'name' => ['Something went wrong! Please try again later.'],
            ]);
        }
    }

    public function verifiedReceipt(Request $request): View
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getIdByOfflineSaleId($request->get('offline_sale_id'));

        return view('front.verify-receipt.verified-receipt', [
            'themeColor' => $this->getThemeColor(),
            'sale' => $sale,
            'saleItems' => $sale ? $this->getPreparedSaleItems($sale->saleItems) : [],
        ]);
    }

    public function notGenuineReceipt(Request $request): View
    {
        return view('front.verify-receipt.not-genuine-receipt', [
            'themeColor' => $this->getThemeColor(),
            'receiptNumber' => $request->get('receipt_number'),
        ]);
    }

    public function genuineReceiptMember(Request $request): View
    {
        return view('front.verify-receipt.genuine-receipt-member', [
            'themeColor' => $this->getThemeColor(),
            'receiptNumber' => $request->get('receipt_number'),
        ]);
    }

    public function addNotGenuineReceipt(
        GenuineReceiptVerificationUpdateData $genuineReceiptVerificationUpdateData,
    ): RedirectResponse|View {
        try {
            $notGenuineVerificationData = $genuineReceiptVerificationUpdateData->toArray();
            $notGenuineVerificationData['is_genuine'] = false;
            unset($notGenuineVerificationData['captcha']);

            $genuineReceiptVerificationQueries = resolve(GenuineReceiptVerificationQueries::class);

            $genuineReceiptVerification = $genuineReceiptVerificationQueries->addNew($notGenuineVerificationData);

            session()->put('genuineReceiptVerificationId', $genuineReceiptVerification->id);

            return view('front.verify-receipt.thank-you', [
                'themeColor' => $this->getThemeColor(),
            ]);
        } catch (Throwable) {
            return to_route('front.genuine_receipt_verification.index')->with(
                'errors',
                'Something went wrong! Please try again later.'
            );
        }
    }

    public function addGenuineReceiptMember(
        GenuineReceiptVerificationMemberData $genuineReceiptVerificationMemberData,
    ): RedirectResponse|View {
        try {
            $saleQueries = resolve(SaleQueries::class);
            $sale = $saleQueries->getIdByOfflineSaleId($genuineReceiptVerificationMemberData->receipt_number);

            $genuineVerificationMemberData = $genuineReceiptVerificationMemberData->toArray();
            $genuineVerificationMemberData['is_genuine'] = true;
            $genuineVerificationMemberData['sale_id'] = $sale?->id;
            unset($genuineVerificationMemberData['captcha']);

            $genuineReceiptVerificationQueries = resolve(GenuineReceiptVerificationQueries::class);

            $genuineReceiptVerification = $genuineReceiptVerificationQueries->addNew($genuineVerificationMemberData);

            session()->put('genuineReceiptVerificationId', $genuineReceiptVerification->id);

            return to_route('front.genuine_receipt_verification.verified_receipt', [
                'offline_sale_id' => $genuineReceiptVerification->receipt_number,
            ]);
        } catch (Throwable) {
            return to_route('front.genuine_receipt_verification.index')->with(
                'errors',
                'Something went wrong! Please try again later.'
            );
        }
    }

    private function getPreparedSaleItems(Collection $saleItems): array
    {
        return $saleItems->map(function ($saleItem): array {
            /** @var Product $product */
            $product = $saleItem->product;

            return [
                'id' => $saleItem->getKey(),
                'product' => $product->getName(),
                'quantity' => $saleItem->getQuantity(),
                'thumbnail' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            ];
        })->toArray();
    }

    private function getMember(?Sale $sale): ?Member
    {
        if (! $sale instanceof Sale) {
            return null;
        }

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        if (null === $sale->member_id) {
            return null;
        }

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getByMemberForGenuineReceipt($sale->member_id, $location->company_id);
    }

    private function getThemeColor(): string
    {
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

        return $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
            $getSiteConfigurationTheme->value
        ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);
    }
}
