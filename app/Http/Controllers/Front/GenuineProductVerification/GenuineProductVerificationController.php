<?php

namespace App\Http\Controllers\Front\GenuineProductVerification;

use App\Domains\GenuineProductVerification\DataObjects\GenuineProductVerificationData;
use App\Domains\GenuineProductVerification\DataObjects\GenuineProductVerificationUpdateData;
use App\Domains\GenuineProductVerification\GenuineProductVerificationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\ProductQueries;
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
use Barryvdh\Snappy\Facades\SnappyImage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Share;
use Throwable;

class GenuineProductVerificationController extends Controller
{
    public function index(): View
    {
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

        $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
            $getSiteConfigurationTheme->value
        ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

        return view('front/verify-product/verify-product', [
            'themeColor' => $themeColor,
            'cookieValue' => Cookie::get('verify-product'),
        ]);
    }

    public function store(GenuineProductVerificationData $genuineProductVerificationData): RedirectResponse|View
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getIdByOfflineSaleId($genuineProductVerificationData->receipt_number);

        $member = $this->getMember($genuineProductVerificationData, $sale);

        $product = null;
        if ($sale instanceof Sale) {
            $product = $sale->saleItems->firstWhere(
                'product.verification_qr_code',
                $genuineProductVerificationData->qr_code
            )?->product;
        }

        $isGenuine = (bool) $product?->id;
        try {
            $productVerificationData = $genuineProductVerificationData->toArray();
            $productVerificationData['product_id'] = $product?->id;
            $productVerificationData['sale_id'] = $sale?->id;
            $productVerificationData['member_id'] = $member?->id;
            $productVerificationData['is_genuine'] = $isGenuine;
            unset($productVerificationData['captcha']);

            $genuineProductVerificationQueries = resolve(GenuineProductVerificationQueries::class);

            $genuineProductVerification = $genuineProductVerificationQueries->addNew($productVerificationData);

            session()->put('genuineProductVerificationId', $genuineProductVerification->id);

            $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

            $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

            $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
                $getSiteConfigurationTheme->value
            ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

            $verifiedImageUrl = '';
            $isverifiedImageExist = false;

            /** @var Product $product */
            if ($product->hasMedia('social_share')) {
                $verifiedImageUrl = $product->getFirstMediaUrl('social_share');
                $isverifiedImageExist = true;
            }

            /** @phpstan-ignore-next-line */
            $socialLinks = Share::page($verifiedImageUrl)
                ->facebook()
                ->twitter()
                ->linkedin()
                ->whatsapp()
                ->getRawLinks();

            return view('front.verify-product.verified-product', [
                'themeColor' => $themeColor,
                'isGenuine' => $isGenuine,
                'product' => $product,
                'socialLinks' => $socialLinks,
                'isverifiedImageExist' => $isverifiedImageExist,
            ]);
        } catch (Throwable) {
            return back()->withErrors([
                'name' => ['Something went wrong! Please try again later.'],
            ]);
        }
    }

    public function update(
        GenuineProductVerificationUpdateData $genuineProductVerificationUpdateData,
    ): RedirectResponse|View {
        try {
            $genuineProductVerificationData = [
                'remarks' => $genuineProductVerificationUpdateData->remarks,
            ];

            $genuineProductVerificationId = session('genuineProductVerificationId');
            session()->forget(['genuineProductVerificationId']);

            $genuineProductVerificationQueries = resolve(GenuineProductVerificationQueries::class);
            $genuineProductVerification = $genuineProductVerificationQueries->getById($genuineProductVerificationId);

            $genuineProductVerificationQueries->update($genuineProductVerification, $genuineProductVerificationData);

            $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

            $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

            $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
                $getSiteConfigurationTheme->value
            ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

            return view('front.verify-product.thank-you', [
                'themeColor' => $themeColor,
            ]);
        } catch (Throwable) {
            return to_route('front.genuine_product_verification.index')->with(
                'errors',
                'Something went wrong! Please try again later.'
            );
        }
    }

    private function getMember(
        GenuineProductVerificationData $genuineProductVerificationData,
        ?Sale $sale,
    ): ?Member {
        if (! $sale instanceof Sale) {
            return null;
        }

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getMemberByMobileNumber(
            $genuineProductVerificationData->mobile_number,
            $location->company_id
        );
    }

    public function generateVerifiedImage(Request $request): JsonResponse
    {
        $status = false;

        $genuineProductVerificationId = session('genuineProductVerificationId');

        $genuineProductVerificationQueries = resolve(GenuineProductVerificationQueries::class);
        $genuineProductVerification = $genuineProductVerificationQueries->getById($genuineProductVerificationId);

        $productQueries = resolve(ProductQueries::class);
        /** @var Product $product */
        $product = $productQueries->getByIdWithVerificationQrCode((int) $genuineProductVerification->product_id);

        if ($product->hasMedia('social_share')) {
            $verifiedImageUrl = $product->getFirstMediaUrl('social_share');
            $status = true;
        } else {
            $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
            $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();
            $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
                $getSiteConfigurationTheme->value
            ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

            $verifiedImageHtml = view('front.verify-product.generate-verified-product', [
                'themeColor' => $themeColor,
                'product' => $product,
            ])->render();

            $verifiedImage = SnappyImage::loadHTML($verifiedImageHtml)
                ->setOption('format', 'png')
                ->output();

            $productQueries->uploadVerifiedImage($product, $verifiedImage);

            $product = $productQueries->refresh($product);
            $verifiedImageUrl = $product->getFirstMediaUrl('social_share');
            $status = true;
        }

        return response()->json([
            'success' => $status,
            'verifiedImageUrl' => $verifiedImageUrl,
        ]);
    }
}
