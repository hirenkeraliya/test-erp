<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Services\DreamPriceEcommerceCheckService;
use App\Domains\DreamPrice\Services\DreamPriceService;
use App\Domains\DreamPriceChannelReference\DreamPriceChannelReferenceQueries;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Services\CartWideAsPerAmountPromotionServiceForEcommerce;
use App\Domains\Promotion\Services\CartWideAsPerPaymentTypePromotionServiceForEcommerce;
use App\Domains\PromotionChannelReference\PromotionChannelReferenceQueries;
use App\Domains\Voucher\Services\VoucherEcommerceDiscountService;
use App\Domains\Voucher\VoucherQueries;
use App\Models\DreamPrice;
use App\Models\Member;
use App\Models\Promotion;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrderEcommerceDiscountService
{
    public CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService;

    public ?Voucher $voucher = null;

    public ?Collection $promotions = null;

    public Collection $dreamPrices;

    public function setDetails(CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService): void
    {
        $this->checkOrderEcommerceDetailsService = $checkOrderEcommerceDetailsService;
        $this->voucher = $this->getVoucher();
        $this->promotions = $this->getPromotions();
        $this->dreamPrices = $this->getDreamPrices();
    }

    public function getPromotions(): Collection
    {
        $promotionQueries = resolve(PromotionQueries::class);
        $promotionChannelReferenceQueries = resolve(PromotionChannelReferenceQueries::class);

        /** @var array $promotionIds */
        $promotionIds = $promotionChannelReferenceQueries->getByPromotionIdAsAndSaleChannelId(
            $this->getPromotionIds(),
            $this->checkOrderEcommerceDetailsService->saleChannel->id
        );

        return $promotionQueries->getByIdsWithRelations(
            $promotionIds,
            $this->checkOrderEcommerceDetailsService->companyId,
        );
    }

    public function getDreamPrices(): Collection
    {
        $dreamPriceChannelReferenceQueries = resolve(DreamPriceChannelReferenceQueries::class);
        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        $dreamPriceIds = $dreamPriceChannelReferenceQueries->getByDreamPriceIdAsAndSaleChannelId(
            $this->getDreamPriceIds(),
            $this->checkOrderEcommerceDetailsService->saleChannel->id
        );

        if (empty($dreamPriceIds)) {
            return collect();
        }

        return $dreamPriceQueries->getByIdsWithProductsAndLocations(
            $dreamPriceIds,
            $this->checkOrderEcommerceDetailsService->companyId,
        );
    }

    /**
     * @return mixed[]
     */
    public function getDreamPriceIds(): array
    {
        return $this->checkOrderEcommerceDetailsService->orderItems->pluck('dream_price_id')
            ->unique()
            ->filter()
            ->toArray();
    }

    public function getPromotionIds(): array
    {
        $promotionIds = [];

        if (! $this->checkOrderEcommerceDetailsService->hasCartPromotion()) {
            return $promotionIds;
        }

        $promotionIds[] = $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id;

        return $promotionIds;
    }

    public function getVoucher(): ?Voucher
    {
        if (! $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number) {
            return null;
        }

        $voucherQueries = resolve(VoucherQueries::class);

        return $voucherQueries->getByVoucherNumberAndCompanyIdWithProductsAndCategories(
            $this->checkOrderEcommerceDetailsService->orderECommerceData->voucher_number,
            $this->checkOrderEcommerceDetailsService->companyId,
        );
    }

    public function checkVoucherDetails(float $cartSubtotal): void
    {
        if (! $this->checkOrderEcommerceDetailsService->hasVoucher()) {
            return;
        }

        $voucherEcommerceDiscountService = resolve(VoucherEcommerceDiscountService::class);
        $voucherEcommerceDiscountService->checkForApplicability(
            $this->checkOrderEcommerceDetailsService,
            $this->voucher,
            $cartSubtotal
        );
    }

    public function getOrderDiscountAmountFor(float $subtotal): array
    {
        $orderDiscount = [
            'cart_wide_discount' => 0,
            'voucher_discount' => 0,
            'cart_wide_loyalty_point_discount' => 0,
            'total_discount' => 0,
        ];

        if ($this->checkOrderEcommerceDetailsService->hasCartPromotion() && $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id) {
            $promotion = null;
            $promotionChannelReferenceQueries = resolve(PromotionChannelReferenceQueries::class);
            $promotionChannelReference = $promotionChannelReferenceQueries->getByExternalPromotionIdAndSaleChannelId(
                $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id,
                $this->checkOrderEcommerceDetailsService->saleChannel->id
            );

            if ($promotionChannelReference && $this->promotions instanceof Collection) {
                $promotion = $this->promotions->firstWhere('id', $promotionChannelReference->promotion_id);
            }

            if ($promotion && in_array(
                $promotion->cart_wide_promotion_type_id,
                [CartWidePromotionTypes::AS_PER_AMOUNT->value, CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value]
            )) {
                $cartWidePromotionDiscount = 0;
                if ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_AMOUNT->value) {
                    $cartWideAsPerAmountPromotionServiceForEcommerce = resolve(
                        CartWideAsPerAmountPromotionServiceForEcommerce::class
                    );
                    $cartWidePromotionDiscount = $cartWideAsPerAmountPromotionServiceForEcommerce->getCartDiscountAmount(
                        $this->checkOrderEcommerceDetailsService
                    );
                } elseif ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                    $cartWideAsPerPaymentTypePromotionServiceForEcommerce = resolve(
                        CartWideAsPerPaymentTypePromotionServiceForEcommerce::class
                    );
                    $cartWidePromotionDiscount = $cartWideAsPerPaymentTypePromotionServiceForEcommerce->getCartDiscountAmount(
                        $this->checkOrderEcommerceDetailsService
                    );
                }

                $orderDiscount['cart_wide_discount'] = $cartWidePromotionDiscount;
                $orderDiscount['total_discount'] = $cartWidePromotionDiscount;
                $subtotal -= $cartWidePromotionDiscount;
            }
        }

        if ($this->checkOrderEcommerceDetailsService->hasVoucher() && $this->voucher instanceof Voucher) {
            $voucherEcommerceDiscountService = resolve(VoucherEcommerceDiscountService::class);

            $voucherDiscount = $voucherEcommerceDiscountService->getDiscountAmount(
                $this->checkOrderEcommerceDetailsService
            );

            $orderDiscount['voucher_discount'] = $voucherDiscount;
            $orderDiscount['total_discount'] += $voucherDiscount;
            $subtotal -= $voucherDiscount;
        }

        if ($this->checkOrderEcommerceDetailsService->hasLoyaltyPointsForCart()) {
            $loyaltyPointDiscount = $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_loyalty_point_amount;
            $orderDiscount['cart_wide_loyalty_point_discount'] = $loyaltyPointDiscount;
            $orderDiscount['total_discount'] += $loyaltyPointDiscount;
            $subtotal -= $loyaltyPointDiscount;
        }

        return $orderDiscount;
    }

    public function getTotalItemDiscountAmount(): float
    {
        $totalItemDiscount = 0.00;
        foreach ($this->checkOrderEcommerceDetailsService->orderItems as $cartItem) {
            $itemDiscounts = $this->getItemDiscountAmountFor($cartItem);
            $totalItemDiscount += $itemDiscounts['total_discount'];
        }

        return $totalItemDiscount;
    }

    public function getItemDiscountAmountFor(array $cartItem): array
    {
        $discounts = [
            'dream_price_discount' => 0.00,
            'item_wise_discount' => 0.00,
            'total_discount' => 0.00,
        ];

        if ($this->checkOrderEcommerceDetailsService->hasDreamPrice($cartItem)) {
            $dreamPriceService = resolve(DreamPriceService::class);
            $dreamPriceDiscountAmount = $dreamPriceService->getDiscountFor($cartItem);
            $discounts['dream_price_discount'] = $dreamPriceDiscountAmount;
            $discounts['total_discount'] += $dreamPriceDiscountAmount;
        }

        if ($this->checkOrderEcommerceDetailsService->hasItemPromotion($cartItem)) {
            $itemDiscount = (float) $cartItem['item_discount_amount'];

            $discounts['item_wise_discount'] = $itemDiscount;
            $discounts['total_discount'] += $itemDiscount;
        }

        return $discounts;
    }

    public function getItemOrderDiscountAmount(float $cartSubtotal, float $itemSubtotal): float
    {
        $cartDiscount = $this->getOrderDiscountAmountFor($cartSubtotal);

        if ($cartSubtotal > 0) {
            return CommonFunctions::numberFormat($itemSubtotal * $cartDiscount['total_discount'] / $cartSubtotal);
        }

        return 0.00;
    }

    public function getItemCartDiscountAmount(float $cartSubtotal, float $itemSubtotal): float
    {
        $cartDiscount = $this->getOrderDiscountAmountFor($cartSubtotal);
        if ($cartSubtotal > 0) {
            return CommonFunctions::numberFormat($itemSubtotal * $cartDiscount['total_discount'] / $cartSubtotal);
        }

        return 0.00;
    }

    public function checkItemWisePromotionDetails(array $cartItem): void
    {
        if ($this->checkOrderEcommerceDetailsService->hasDreamPrice($cartItem)) {
            $dreamPriceChannelReferenceQueries = resolve(DreamPriceChannelReferenceQueries::class);
            $dreamPriceChannelReference = $dreamPriceChannelReferenceQueries->getByExternalDreamPriceIdAndSaleChannelId(
                (int) $cartItem['dream_price_id'], $this->checkOrderEcommerceDetailsService->saleChannel->id);

            if (empty($dreamPriceChannelReference)) {
                return;
            }

            /** @var DreamPrice $dreamPrice */
            $dreamPrice = $this->dreamPrices->firstWhere('id', $dreamPriceChannelReference->dream_price_id);

            $dreamPriceEcommerceCheckService = resolve(DreamPriceEcommerceCheckService::class);

            $dreamPriceEcommerceCheckService->checkForApplicability(
                $this->checkOrderEcommerceDetailsService,
                $dreamPrice,
                $cartItem
            );
        }
    }

    public function checkCartWidePromotionDetails(float $subtotal): void
    {
        if ($this->checkOrderEcommerceDetailsService->hasCartPromotion() && $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id) {
            $promotion = null;
            $promotionChannelReferenceQueries = resolve(PromotionChannelReferenceQueries::class);
            $promotionChannelReference = $promotionChannelReferenceQueries->getByExternalPromotionIdAndSaleChannelId(
                $this->checkOrderEcommerceDetailsService->orderECommerceData->cart_promotion_id,
                $this->checkOrderEcommerceDetailsService->saleChannel->id
            );

            if ($promotionChannelReference && $this->promotions instanceof Collection) {
                /** @var Promotion $promotion */
                $promotion = $this->promotions->firstWhere('id', $promotionChannelReference->promotion_id);
            }

            if (! $promotion || collect($promotion)->isEmpty()) {
                abort(412, 'Specified promotion is not available in our records.');
            }

            if (! $promotion->is_automatic) {
                abort(412, 'The Selected Promotion Is Manual And Promo Code Is Not Provided, Specify The Promo Code.');
            }

            $this->checkMember($promotion);
            $this->checkWalkInMember($promotion);
            $this->checkPromotionMembership($promotion);
            $this->checkPromotionIsActive($promotion);
            $this->checkPromotionTimeFrame($promotion);
            $this->checkPromotionLocations($promotion);
            $this->checkCartWisePromotionRestrictions($promotion);

            $subtotal = $this->checkOrderEcommerceDetailsService->getCartSubtotalByDiscountApplicableType($subtotal);
            if ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_AMOUNT->value) {
                $cartWideAsPerAmountPromotionServiceForEcommerce = resolve(
                    CartWideAsPerAmountPromotionServiceForEcommerce::class
                );
                $cartWideAsPerAmountPromotionServiceForEcommerce->checkForApplicability(
                    $this->checkOrderEcommerceDetailsService,
                    $subtotal,
                    $promotion
                );
            } elseif ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                $cartWideAsPerPaymentTypePromotionServiceForEcommerce = resolve(
                    CartWideAsPerPaymentTypePromotionServiceForEcommerce::class
                );
                $cartWideAsPerPaymentTypePromotionServiceForEcommerce->checkForApplicability(
                    $this->checkOrderEcommerceDetailsService,
                    $promotion
                );
            }
        }
    }

    public function checkMember(Promotion $promotion): void
    {
        if (! $promotion->allow_registered_member && $this->isMemberAttached()) {
            $orderMismatchMessage = 'Specified promotion is not allowed for the registered members.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );

            return;
        }

        if (! $this->isMemberAttached()) {
            return;
        }

        if ($promotion->memberGroups->isEmpty()) {
            return;
        }

        if (
            $this->checkOrderEcommerceDetailsService->member instanceof Member
            && $this->checkOrderEcommerceDetailsService->member->memberGroupMembers->whereIn(
                'member_group_id',
                $promotion->memberGroups->pluck('id')
            )->isNotEmpty()
        ) {
            return;
        }

        $orderMismatchMessage = 'Member is not valid for the specified promotion.';
        CommonFunctions::addMismatchOrAbort(
            $this->checkOrderEcommerceDetailsService->orderMismatches,
            $orderMismatchMessage
        );
    }

    public function isMemberAttached(): bool
    {
        if ($this->checkOrderEcommerceDetailsService->isMemberAttached()) {
            return true;
        }

        return $this->checkOrderEcommerceDetailsService->hasMemberDetails();
    }

    public function checkWalkInMember(Promotion $promotion): void
    {
        if ($promotion->allow_walk_in_member) {
            return;
        }

        if ($this->isMemberAttached()) {
            return;
        }

        $orderMismatchMessage = 'Specified promotion is not allowed for the walk in member.';
        CommonFunctions::addMismatchOrAbort(
            $this->checkOrderEcommerceDetailsService->orderMismatches,
            $orderMismatchMessage
        );
    }

    public function checkPromotionMembership(Promotion $promotion): void
    {
        if (! $promotion->is_membership_required) {
            return;
        }

        if ($promotion->memberships->isEmpty()) {
            return;
        }

        if (! $this->isMemberAttached()) {
            $orderMismatchMessage = 'Member and Membership is required for the specified promotion.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }

        if (
            $this->checkOrderEcommerceDetailsService->member instanceof Member
            && $this->checkOrderEcommerceDetailsService->member->membership_id
            && $promotion->memberships->firstWhere(
                'id',
                $this->checkOrderEcommerceDetailsService->member->membership_id
            )
        ) {
            return;
        }

        $orderMismatchMessage = 'The Selected Member membership is not valid for the specified promotion.';
        CommonFunctions::addMismatchOrAbort(
            $this->checkOrderEcommerceDetailsService->orderMismatches,
            $orderMismatchMessage
        );
    }

    public function checkPromotionIsActive(Promotion $promotion): void
    {
        if (false === $promotion->status) {
            $orderMismatchMessage = 'Specified promotion is inactive.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }
    }

    public function checkPromotionTimeFrame(Promotion $promotion): void
    {
        if ($promotion->timeframe_type_id === PromotionTimeframeTypes::NO_LIMIT->value) {
            return;
        }

        if ($promotion->timeframe_type_id === PromotionTimeframeTypes::LIMITED_BY_DATES->value) {
            $this->checkLimitedByDates($promotion);

            return;
        }

        if ($promotion->timeframe_type_id === PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value) {
            $this->checkLimitByDayOfTheWeek($promotion);

            return;
        }

        if ($promotion->timeframe_type_id === PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value) {
            $this->checkLimitByDayOfTheMonth($promotion);

            return;
        }

        $this->checkLimitByHourOfTheDay($promotion);
    }

    public function checkLimitedByDates(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s', $this->checkOrderEcommerceDetailsService->orderECommerceData->happened_at ?? Carbon::now()->format(
                'Y-m-d H:i:s'
            )
        );
        $happenedAt = $happenedAtFormat->format('Y-m-d');

        if ($promotion->start_date > $happenedAt || $promotion->end_date < $happenedAt) {
            $orderMismatchMessage = 'Specified promotion is available between ' . $promotion->start_date . ' and ' . $promotion->end_date . ' only. The sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }
    }

    public function checkLimitByDayOfTheWeek(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s', $this->checkOrderEcommerceDetailsService->orderECommerceData->happened_at ?? Carbon::now()->format(
                'Y-m-d H:i:s'
            )
        );
        $happenedAtDay = $happenedAtFormat->format('w');

        if (! $promotion->weekly->firstWhere('week_day', $happenedAtDay)) {
            $orderMismatchMessage = 'Promotion is not allowed on this week day.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }
    }

    public function checkLimitByDayOfTheMonth(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s', $this->checkOrderEcommerceDetailsService->orderECommerceData->happened_at ?? Carbon::now()->format(
                'Y-m-d H:i:s'
            )
        );
        $happenedAtDate = $happenedAtFormat->format('d');

        if (! $promotion->monthly->firstWhere('month_date', $happenedAtDate)) {
            $orderMismatchMessage = 'Promotion is not allowed on this day of the month.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }
    }

    public function checkLimitByHourOfTheDay(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s', $this->checkOrderEcommerceDetailsService->orderECommerceData->happened_at ?? Carbon::now()->format(
                'Y-m-d H:i:s'
            )
        );
        $happenedAt = $happenedAtFormat->format('Y-m-d');
        $happenedAtTime = $happenedAtFormat->format('H:i');

        if ($promotion->start_date !== $happenedAt) {
            $orderMismatchMessage = 'Promotion is not allowed on this date.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }

        if ($promotion->start_time > $happenedAtTime || $promotion->end_time < $happenedAtTime) {
            $orderMismatchMessage = 'Promotion is not allowed at this time of the day.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }
    }

    public function checkPromotionLocations(Promotion $promotion): void
    {
        if ($promotion->locations->isEmpty()) {
            return;
        }

        if ($promotion->locations->firstWhere('id', $this->checkOrderEcommerceDetailsService->location->id)) {
            return;
        }

        $orderMismatchMessage = 'Specified promotion is not available for the location ' . $this->checkOrderEcommerceDetailsService->location->name;
        CommonFunctions::addMismatchOrAbort(
            $this->checkOrderEcommerceDetailsService->orderMismatches,
            $orderMismatchMessage
        );
    }

    public function checkCartWisePromotionRestrictions(Promotion $promotion): void
    {
        if ($promotion->dream_price_applicable) {
            return;
        }

        foreach ($this->checkOrderEcommerceDetailsService->orderItems as $cartItem) {
            if ($this->checkOrderEcommerceDetailsService->hasDreamPrice($cartItem)) {
                $orderMismatchMessage = 'Specified promotion cannot be applied with the dream price';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkOrderEcommerceDetailsService->orderMismatches,
                    $orderMismatchMessage
                );

                return;
            }
        }
    }
}
