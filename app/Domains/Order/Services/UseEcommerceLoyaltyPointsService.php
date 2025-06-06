<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Domains\OrderLoyaltyPoint\OrderLoyaltyPointQueries;
use App\Models\Member;
use App\Models\Order;

class UseEcommerceLoyaltyPointsService
{
    public function checkLoyaltyPointsCartDiscount(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
    ): void {
        if (! $checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart()) {
            return;
        }

        $member = $this->checkUserLoyaltyPoints($checkOrderEcommerceDetailsService);

        $orderECommerceData = $checkOrderEcommerceDetailsService->orderECommerceData;

        $this->checkLoyaltyPointsIsValidOrNot(
            $checkOrderEcommerceDetailsService,
            $member,
            (int) $orderECommerceData->cart_loyalty_points
        );

        $amountFromLoyaltyPoints = 0;
        if ($member->membership && $member->membership->loyalty_points_per_currency_unit > 0) {
            $amountFromLoyaltyPoints = CommonFunctions::numberFormat(
                (int) $orderECommerceData->cart_loyalty_points / $member->membership->loyalty_points_per_currency_unit
            );
        }

        if (CommonFunctions::compareFloatNumbers(
            $amountFromLoyaltyPoints,
            (float) $orderECommerceData->cart_loyalty_point_amount
        )) {
            return;
        }

        $saleMismatchMessage = 'The specified amount (' . $orderECommerceData->cart_loyalty_point_amount . ') is more than the calculated amount from the loyalty points as per the membership of the user (' . $amountFromLoyaltyPoints . ').';
        CommonFunctions::addMismatchOrAbort($checkOrderEcommerceDetailsService->orderMismatches, $saleMismatchMessage);
    }

    public function checkUserLoyaltyPoints(CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService): Member
    {
        $member = $checkOrderEcommerceDetailsService->member;

        if (! $member instanceof Member) {
            abort(412, 'User is compulsory when used loyalty point');
        }

        if (! $member->membership_id) {
            abort(412, 'Loyalty points can only be used when membership is assigned to the user.');
        }

        $useLoyaltyPoints = (int) $checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_points;

        if ($member->loyalty_points < $useLoyaltyPoints) {
            $saleMismatchMessage = 'Specified loyalty points are more than the current loyalty points balance of the user.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        return $member;
    }

    public function checkLoyaltyPointsIsValidOrNot(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        Member $member,
        int $paymentLoyaltyPoints
    ): void {
        if (! $member->membership) {
            return;
        }

        $minPoints = $member->membership->min_loyalty_points_for_redemption;
        $maxPoints = $member->membership->max_loyalty_points_for_redemption;

        if (! ($paymentLoyaltyPoints >= $minPoints && $paymentLoyaltyPoints <= $maxPoints)) {
            $saleMismatchMessage = 'The specified loyalty points (' . $paymentLoyaltyPoints . ') are not valid. Loyalty points must be between ' . $minPoints . ' and ' . $maxPoints . '.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }
    }

    public function saveCartWideLoyaltyPointsDiscount(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        Order $order
    ): void {
        if (! $checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart()) {
            return;
        }

        $member = $checkOrderEcommerceDetailsService->member;
        if (! $member instanceof Member) {
            return;
        }

        $memberQueries = resolve(MemberQueries::class);
        $member = $memberQueries->refresh($member);

        $happenedAtFormat = $checkOrderEcommerceDetailsService->getHappenedAtFormat();

        $loyaltyPointService = resolve(LoyaltyPointService::class);
        $loyaltyPointService->decreaseLoyaltyPoints(
            $member,
            (int) $checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_points,
            LoyaltyPointUpdateTypes::USED->value,
            $order->getKey(),
            ModelMapping::ORDER->name,
            $happenedAtFormat->format('Y-m-d H:i:s')
        );

        $orderLoyaltyPointQueries = resolve(OrderLoyaltyPointQueries::class);
        $orderLoyaltyPoint = $orderLoyaltyPointQueries->addNew(
            (int) $checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_points,
            (float) $checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount,
            $order->id,
        );

        $orderDiscountQueries = resolve(OrderDiscountQueries::class);
        $orderDiscountQueries->addNew(
            $order->id,
            $orderLoyaltyPoint->id,
            ModelMapping::ORDER_LOYALTY_POINT->name,
            (float) $checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount
        );
    }
}
