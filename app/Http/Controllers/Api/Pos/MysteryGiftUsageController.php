<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\Domains\MysteryGift\MysteryGiftUsagesQueries;
use App\Domains\MysteryGift\Resources\MysteryGiftUsageDetails;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class MysteryGiftUsageController extends Controller
{
    public function getCouponCodeDetails(Request $request): array
    {
        $validatedData = $request->validate([
            'coupon_code' => ['required', 'string', 'exists:mystery_gift_usages,coupon_code'],
        ]);

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $mysteryGiftUsagesQueries = resolve(MysteryGiftUsagesQueries::class);
        $mysteryGiftUsage = $mysteryGiftUsagesQueries->getDetailsByCouponCode($validatedData['coupon_code']);

        if (! $mysteryGiftUsage) {
            abort(412, 'Coupon code not found.');
        }

        return [
            'coupon_code_details' => new MysteryGiftUsageDetails($mysteryGiftUsage),
        ];
    }

    public function updateCouponCodeDetails(Request $request): void
    {
        $validatedData = $request->validate([
            'coupon_code' => ['required', 'string', 'exists:mystery_gift_usages,coupon_code'],
            'used_at' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $mysteryGiftUsagesQueries = resolve(MysteryGiftUsagesQueries::class);
        $mysteryGiftUsage = $mysteryGiftUsagesQueries->getDetailsByCouponCodeOnlyNotUsedAt(
            $validatedData['coupon_code']
        );

        if (! $mysteryGiftUsage) {
            abort(412, 'Coupon code already used.');
        }

        $mysteryGiftUsagesQueries->updateUsedAt($mysteryGiftUsage, $validatedData['used_at']);
    }
}
