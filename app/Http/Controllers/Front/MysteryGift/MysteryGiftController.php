<?php

namespace App\Http\Controllers\Front\MysteryGift;

use App\Domains\Member\DataObjects\FrontMysteryGiftMemberData;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\MemberQueries;
use App\Domains\MysteryGift\MysteryGiftQueries;
use App\Domains\MysteryGift\Services\MysteryGiftUsageService;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\MysteryGift;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Throwable;

class MysteryGiftController extends Controller
{
    public function __construct(
        protected MysteryGiftQueries $mysteryGiftQueries
    ) {
    }

    public function index(): View
    {
        return view('front.mystery_gifts.index', [
            'isActive' => (bool) $this->mysteryGiftQueries->getActiveConfigurations(),
        ]);
    }

    public function verifyReceipt(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'receipt' => 'required|string',
        ]);

        return $this->validateReceiptData($validatedData);
    }

    public function registerMember(FrontMysteryGiftMemberData $frontMysteryGiftMemberData): JsonResponse
    {
        $memberQueries = resolve(MemberQueries::class);

        $saleQueries = resolve(SaleQueries::class);

        /** @var Sale $sale */
        $sale = $saleQueries->getByOfflineIdWithLocation($frontMysteryGiftMemberData->receipt);

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        $memberDetails = $frontMysteryGiftMemberData->all();
        unset($memberDetails['receipt']);

        try {
            /** @var int $memberId */
            $memberId = $memberQueries->addNewMemberAndReturnId(
                $memberDetails,
                $location->id,
                $location->company_id,
                MemberChannelEnum::POS->value
            );

            $saleQueries = resolve(SaleQueries::class);
            $saleQueries->addMemberToSale($sale, $memberId);

            return response()->json([
                'success' => true,
                'message' => 'Member registered successfully',
            ]);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! Please try again later',
            ]);
        }
    }

    public function getReward(Request $request): JsonResponse
    {
        $saleQueries = resolve(SaleQueries::class);

        /** @var Sale $sale */
        $sale = $saleQueries->getByOfflineIdWithLocation($request->receipt);

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        $companyId = $location->company_id;

        $mysteryGiftQueries = resolve(MysteryGiftQueries::class);

        /** @var MysteryGift|null $mysteryGift */
        $mysteryGift = $mysteryGiftQueries->getMysteryGiftConfigurations($companyId);

        if (! $mysteryGift instanceof MysteryGift) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, no offer available for you.',
            ]);
        }

        $mysteryGiftUsageService = resolve(MysteryGiftUsageService::class);
        $reward = $mysteryGiftUsageService->generateVoucherOrPromotion($mysteryGift, $sale);

        if (false === $reward['status']) {
            return response()->json([
                'success' => false,
                'message' => $reward['message'],
            ]);
        }

        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);
        $barcodeDetails = $barcodeGeneratorPNG->getBarcode(
            $reward['coupon_code'],
            $barcodeGeneratorPNG::TYPE_CODE_128,
            3,
            150
        );

        $reward['bar_code'] = base64_encode($barcodeDetails);

        return response()->json([
            'success' => true,
            'reward' => $reward,
        ]);
    }

    private function validateReceiptData(array $validatedData): JsonResponse
    {
        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->getByOfflineIdWithLocation($validatedData['receipt']);

        if (! $sale instanceof Sale) {
            return response()->json([
                'success' => false,
                'hasMember' => null,
                'message' => 'Invalid receipt number.',
            ]);
        }

        if (! in_array($sale->status, [
            SaleStatus::REGULAR_SALE->value,
            SaleStatus::COMPLETE_LAYAWAY_SALE->value,
            SaleStatus::COMPLETE_CREDIT_SALE->value,
        ])) {
            return response()->json([
                'success' => false,
                'hasMember' => null,
                'message' => 'Nice try, You are not eligible for this discount.',
            ]);
        }

        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $sale->counterUpdate;

        /** @var Counter $counter */
        $counter = $counterUpdate->counter;

        /** @var Location $location */
        $location = $counter->location;

        $mysteryGift = $this->mysteryGiftQueries->getActiveConfigurations($location->company_id);

        if (! $mysteryGift instanceof MysteryGift) {
            return response()->json([
                'success' => false,
                'hasMember' => null,
                'message' => 'Sorry, you are late this offer is already expired.',
            ]);
        }

        if (false === $this->isDatesValid($mysteryGift, $sale->happened_at)) {
            return response()->json([
                'success' => false,
                'hasMember' => null,
                'message' => 'Sorry, you are late this offer is already expired.',
            ]);
        }

        /* @phpstan-ignore-next-line */
        if ((float) $sale->sale_items_sum_quantity <= (float) $sale->mystery_gift_usages_count) {
            return response()->json([
                'success' => false,
                'hasMember' => null,
                'message' => 'This discount has already been used for this receipt number.',
            ]);
        }

        if ($mysteryGift->minimum_spend > 0 && ((float) $sale->total_amount_paid < (float) $mysteryGift->minimum_spend)) {
            return response()->json([
                'success' => false,
                'hasMember' => null,
                'message' => 'Sorry, you are not eligible for this discount.',
            ]);
        }

        return response()->json([
            'success' => true,
            'hasMember' => null !== $sale->member_id,
            'message' => '',
        ]);
    }

    private function isDatesValid(MysteryGift $mysteryGift, string $happenedAt): bool
    {
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        return Carbon::parse($mysteryGift->start_date)->lessThanOrEqualTo($happenedAtFormat)
            && Carbon::parse($mysteryGift->end_date)->greaterThanOrEqualTo($happenedAtFormat);
    }
}
