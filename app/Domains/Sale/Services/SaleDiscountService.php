<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\ComplimentaryItemReason\Services\ComplimentaryItemService;
use App\Domains\Director\DirectorQueries;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Services\DreamPriceService;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountSaleService;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Domains\Promotion\Interfaces\SalePromotionInterface;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Services\CartWideAsPerAmountPromotionService;
use App\Domains\Promotion\Services\CartWideAsPerPaymentTypePromotionService;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemPriceOverride\Services\SaleItemPriceOverrideService;
use App\Domains\SalePriceOverride\Services\SalePriceOverrideService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\Voucher\Services\VoucherDiscountService;
use App\Domains\Voucher\VoucherQueries;
use App\Models\ComplimentaryItemReason;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionPromoCode;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SaleDiscountService
{
    public CheckSaleDetailsService $checkSaleDetailsService;

    public Collection $promotions;

    public Collection $happyHourDiscounts;

    public Collection $dreamPrices;

    public Collection $complimentaryItemReasons;

    public Collection $directors;

    public Collection $storeManagers;

    public ?Voucher $voucher = null;

    public function setDetails(CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $this->checkSaleDetailsService = $checkSaleDetailsService;
        $this->promotions = $this->getPromotions();
        $this->dreamPrices = $this->getDreamPrices();
        $this->voucher = $this->getVoucher();
        $this->complimentaryItemReasons = $this->getComplimentaryItemReasons();
        $this->directors = $this->getDirectors();
        $this->storeManagers = $this->getStoreManagers();
        $this->happyHourDiscounts = $this->getHappyHourDiscounts();
    }

    public function getPromotions(): Collection
    {
        $promotionQueries = resolve(PromotionQueries::class);

        return $promotionQueries->getByIdsWithRelations(
            $this->getPromotionIds(),
            $this->checkSaleDetailsService->companyId,
        );
    }

    public function getHappyHourDiscounts(): Collection
    {
        $happyHourDiscountQueries = resolve(HappyHourDiscountQueries::class);

        return $happyHourDiscountQueries->getByOfflineIdsWithRelations(
            $this->getHappyHourDiscountIds(),
            $this->checkSaleDetailsService->companyId,
        );
    }

    public function getVoucher(): ?Voucher
    {
        if ($this->checkSaleDetailsService->saleData->voucher_number) {
            $voucherQueries = resolve(VoucherQueries::class);

            return $voucherQueries->getByVoucherNumberAndCompanyIdWithProductsAndCategories(
                $this->checkSaleDetailsService->saleData->voucher_number,
                $this->checkSaleDetailsService->companyId,
            );
        }

        return null;
    }

    public function getDreamPrices(): Collection
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        return $dreamPriceQueries->getByIdsWithProductsAndLocations(
            $this->getDreamPriceIds(),
            $this->checkSaleDetailsService->companyId,
        );
    }

    public function getComplimentaryItemReasons(): Collection
    {
        if ($this->getComplimentaryItemReasonIds() === []) {
            return collect([]);
        }

        $complimentaryItemReasonQueries = resolve(ComplimentaryItemReasonQueries::class);

        return $complimentaryItemReasonQueries->getByIdsAndCompanyId(
            $this->getComplimentaryItemReasonIds(),
            $this->checkSaleDetailsService->companyId,
        );
    }

    public function getDirectors(): Collection
    {
        if ($this->complimentaryItemReasons->isEmpty()) {
            return collect([]);
        }

        if ($this->getDirectorIds() === []) {
            return collect([]);
        }

        $directorQueries = resolve(DirectorQueries::class);

        return $directorQueries->getByIds($this->getDirectorIds(), $this->checkSaleDetailsService->companyId);
    }

    public function getStoreManagers(): Collection
    {
        if ($this->complimentaryItemReasons->isEmpty()) {
            return collect([]);
        }

        if ($this->getStoreManagerIds() === []) {
            return collect([]);
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);

        return $storeManagerQueries->getByIds(
            $this->getStoreManagerIds(),
            $this->checkSaleDetailsService->companyId
        );
    }

    /**
     * @return mixed[]
     */
    public function getPromotionIds(): array
    {
        $promotionIds = $this->checkSaleDetailsService->cartItems->pluck('promotion_id')
            ->unique()
            ->filter()
            ->toArray();

        if (! $this->checkSaleDetailsService->hasCartPromotion()) {
            return $promotionIds;
        }

        $promotionIds[] = $this->checkSaleDetailsService->saleData->cart_promotion_id;

        return $promotionIds;
    }

    public function getHappyHourDiscountIds(): array
    {
        return $this->checkSaleDetailsService->cartItems->pluck('happy_hours_offline_id')
            ->unique()
            ->filter()
            ->toArray();
    }

    /**
     * @return mixed[]
     */
    public function getDreamPriceIds(): array
    {
        return $this->checkSaleDetailsService->cartItems->pluck('dream_price_id')
            ->unique()
            ->filter()
            ->toArray();
    }

    /**
     * @return mixed[]
     */
    public function getComplimentaryItemReasonIds(): array
    {
        return $this->checkSaleDetailsService->cartItems->pluck('complimentary_item_reason_id')
            ->unique()
            ->filter()
            ->toArray();
    }

    public function checkCartWidePromotionDetails(float $subtotal): void
    {
        if ($this->checkSaleDetailsService->hasCartPromotion()) {
            /** @var Promotion $promotion */
            $promotion = $this->promotions
                ->firstWhere('id', $this->checkSaleDetailsService->saleData->cart_promotion_id);

            if (collect($promotion)->isEmpty()) {
                abort(412, 'Specified promotion is not available in our records.');
            }

            if (! $promotion->is_automatic && null === $this->checkSaleDetailsService->saleData->cart_promo_code) {
                abort(412, 'The Selected Promotion Is Manual And Promo Code Is Not Provided, Specify The Promo Code.');
            }

            $this->checkCartWidePromoCode($promotion);
            $this->checkMember($promotion);
            $this->checkWalkInMember($promotion);
            $this->checkPromotionMembership($promotion);
            $this->checkEmployee($promotion);
            $this->checkPromotionIsActive($promotion);
            $this->checkPromotionTimeFrame($promotion);
            $this->checkPromotionLocations($promotion);
            $this->checkCartWisePromotionRestrictions($promotion);

            $subtotal = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType($subtotal);
            if ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_AMOUNT->value) {
                $cartWideAsPerAmountPromotionService = resolve(CartWideAsPerAmountPromotionService::class);
                $cartWideAsPerAmountPromotionService->checkForApplicability(
                    $this->checkSaleDetailsService,
                    $subtotal,
                    $promotion
                );
            } elseif ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                $cartWideAsPerPaymentTypePromotionService = resolve(CartWideAsPerPaymentTypePromotionService::class);
                $cartWideAsPerPaymentTypePromotionService->checkForApplicability(
                    $this->checkSaleDetailsService,
                    $promotion
                );
            }
        }
    }

    public function checkVoucherDetails(float $cartSubtotal): void
    {
        $cartSubtotal = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType($cartSubtotal);

        $voucherDiscountService = resolve(VoucherDiscountService::class);
        $voucherDiscountService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->voucher,
            $cartSubtotal
        );
    }

    public function checkPriceOverrideForCartDetails(float $cartSubtotal): void
    {
        $cartSubtotalByDiscountApplicableType = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType(
            $cartSubtotal
        );

        $salePriceOverrideService = resolve(SalePriceOverrideService::class);
        $salePriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $cartSubtotalByDiscountApplicableType,
            $cartSubtotal
        );
    }

    public function checkItemWisePromotionDetails(Product $product, array $cartItem): void
    {
        if ($this->checkSaleDetailsService->hasHappyHourDiscount($cartItem)) {
            $happyHourDiscountSaleService = resolve(HappyHourDiscountSaleService::class);
            $happyHourDiscountSaleService->checkForApplicability($this->checkSaleDetailsService, $cartItem);

            return;
        }

        if ($this->checkSaleDetailsService->hasDreamPrice($cartItem)) {
            /** @var DreamPrice $dreamPrice */
            $dreamPrice = $this->dreamPrices->firstWhere('id', $cartItem['dream_price_id']);

            $dreamPriceService = resolve(DreamPriceService::class);

            $dreamPriceService->checkForApplicability($this->checkSaleDetailsService, $dreamPrice, $cartItem);
        }

        if ($this->checkSaleDetailsService->hasComplimentaryItem($cartItem)) {
            /** @var ComplimentaryItemReason $complimentaryItemReason */
            $complimentaryItemReason = $this->complimentaryItemReasons->firstWhere(
                'id',
                '===',
                (int) $cartItem['complimentary_item_reason_id']
            );

            $complimentaryItemService = resolve(ComplimentaryItemService::class);
            $complimentaryItemService->checkForApplicability(
                $this->checkSaleDetailsService,
                $complimentaryItemReason,
                $cartItem,
                $this->directors,
                $this->storeManagers,
            );

            return;
        }

        if ($this->checkSaleDetailsService->hasProductLoyaltyPoints($cartItem)) {
            if (! array_key_exists('loyalty_point_item_discount', $cartItem)) {
                $saleMismatchMessage = 'Loyalty Points item discount amount not specified.';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            if (array_key_exists('loyalty_point_item_discount', $cartItem)) {
                $discountAmount = $this->checkSaleDetailsService->getItemSubtotal($cartItem);
                $loyaltyPointItemDiscountAmount = (float) $cartItem['loyalty_point_item_discount'];

                if (! CommonFunctions::compareFloatNumbers($loyaltyPointItemDiscountAmount, $discountAmount)) {
                    $saleMismatchMessage = 'Provided loyalty point item discount does not match with calculated amount.\nExpected: ' . CommonFunctions::numberFormat(
                        $discountAmount
                    ) . '\\n' .
                            'Received: ' . CommonFunctions::numberFormat(
                                (float) $cartItem['loyalty_point_item_discount']
                            );
                    CommonFunctions::addMismatchOrAbort(
                        $this->checkSaleDetailsService->saleMismatches,
                        $saleMismatchMessage
                    );
                }
            }
        }

        if ($this->checkSaleDetailsService->hasItemPromotion($cartItem)) {
            /** @var Promotion $promotion */
            $promotion = $this->promotions->firstWhere('id', $cartItem['promotion_id']);

            if (collect($promotion)->isEmpty()) {
                abort(412, 'Specified promotion is not available in our records.');
            }

            if (! $promotion->is_automatic && ! $this->checkSaleDetailsService->hasItemPromoCode($cartItem)) {
                abort(
                    412,
                    'The Selected Promotion Is Manual And Promo Code Is Not Provided, Specify The Promo Code.'
                );
            }

            $this->checkItemWisePromoCode($promotion, $cartItem);
            $this->checkPromotionProductType($cartItem);
            $this->checkMember($promotion);
            $this->checkPromotionMembership($promotion);
            $this->checkWalkInMember($promotion);
            $this->checkEmployee($promotion);
            $this->checkPromotionIsActive($promotion);
            $this->checkPromotionTimeFrame($promotion);
            $this->checkPromotionLocations($promotion);
            $this->checkItemWisePromotionRestrictions($promotion, $cartItem);

            $promotionClass = ItemWisePromotionTypes::getPromotionClass($promotion->item_wise_promotion_type_id);
            if ($promotionClass instanceof SalePromotionInterface) {
                $itemTotal = $this->applyDreamPriceOn($cartItem);
                $itemTotal = $this->checkSaleDetailsService->getItemSubtotalByDiscountApplicableType(
                    $itemTotal,
                    $cartItem
                );

                $promotionClass->checkForApplicability(
                    $this->checkSaleDetailsService,
                    $promotion,
                    $cartItem,
                    $product,
                    $itemTotal,
                    $this,
                );
            }
        }

        if (! $this->checkSaleDetailsService->hasPriceOverride($cartItem)) {
            return;
        }

        $saleItemPriceOverrideService = resolve(SaleItemPriceOverrideService::class);
        $saleItemPriceOverrideService->checkForApplicability($this->checkSaleDetailsService, $cartItem);
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
            $saleMismatchMessage = 'Member and Membership is required for the specified promotion.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (
            $this->checkSaleDetailsService->member instanceof Member
            && $this->checkSaleDetailsService->member->membership_id
            && $promotion->memberships->firstWhere('id', $this->checkSaleDetailsService->member->membership_id)
        ) {
            return;
        }

        $saleMismatchMessage = 'The Selected Member membership is not valid for the specified promotion.';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkMember(Promotion $promotion): void
    {
        if (! $promotion->allow_registered_member && $this->isMemberAttached()) {
            $saleMismatchMessage = 'Specified promotion is not allowed for the registered members.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $this->isMemberAttached()) {
            return;
        }

        if ($promotion->memberGroups->isEmpty()) {
            return;
        }

        if (
            $this->checkSaleDetailsService->member instanceof Member
            && $this->checkSaleDetailsService->member->memberGroupMembers->whereIn(
                'member_group_id',
                $promotion->memberGroups->pluck('id')
            )->isNotEmpty()
        ) {
            return;
        }

        $saleMismatchMessage = 'Member is not valid for the specified promotion.';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkItemWisePromoCode(Promotion $promotion, array $cartItem): void
    {
        if (! array_key_exists('promo_code', $cartItem)) {
            return;
        }

        if (null === $cartItem['promo_code']) {
            return;
        }

        if ($promotion->promotion_applicable_type_id !== PromotionApplicableTypes::ITEM_WISE->value) {
            $saleMismatchMessage = 'The provided promotion is cart wide cannot be used here.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkSaleDetailsService->saleMismatches,
                $saleMismatchMessage
            );
        }

        $promotionPromoCodes = $promotion->promotionPromoCodes;

        $isValidPromoCode = $promotionPromoCodes->where('promo_code', $cartItem['promo_code'])->first();

        if (! $isValidPromoCode instanceof PromotionPromoCode) {
            abort(412, 'The provided promo code is not valid for this promotion.');
        }

        if ($promotion->usage_type === PromotionUsageTypes::SINGLE_USE->value) {
            $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
            $saleItemDiscount = $saleItemDiscountQueries->fetchSaleItemDiscountByPromotionAndPromoCode(
                $promotion->getKey(),
                $cartItem['promo_code']
            );

            if (null === $saleItemDiscount) {
                return;
            }

            $saleMismatchMessage = 'The provided promo code is already been used.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkSaleDetailsService->saleMismatches,
                $saleMismatchMessage
            );
        }
    }

    public function checkCartWidePromoCode(Promotion $promotion): void
    {
        $saleData = $this->checkSaleDetailsService->saleData->toArray();

        if (! array_key_exists('cart_promo_code', $saleData)) {
            return;
        }

        if (null === $saleData['cart_promo_code']) {
            return;
        }

        if ($promotion->promotion_applicable_type_id !== PromotionApplicableTypes::CART_WIDE->value) {
            $saleMismatchMessage = 'The provided promotion is item wise cannot be used here.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkSaleDetailsService->saleMismatches,
                $saleMismatchMessage
            );
        }

        $promotionPromoCodes = $promotion->promotionPromoCodes;

        $isValidPromoCode = $promotionPromoCodes->where('promo_code', $saleData['cart_promo_code'])->first();

        if (! $isValidPromoCode instanceof PromotionPromoCode) {
            abort(412, 'The provided promo code is not valid for this promotion.');
        }

        if ($promotion->usage_type === PromotionUsageTypes::SINGLE_USE->value) {
            $saleDiscountQueries = resolve(SaleDiscountQueries::class);
            $saleDiscount = $saleDiscountQueries->fetchSaleDiscountByPromotionAndPromoCode(
                $promotion->getKey(),
                $saleData['cart_promo_code']
            );

            if (null === $saleDiscount) {
                return;
            }

            $saleMismatchMessage = 'The provided promo code is already been used.';
            CommonFunctions::addMismatchOrAbort(
                $this->checkSaleDetailsService->saleMismatches,
                $saleMismatchMessage
            );
        }
    }

    public function checkWalkInMember(Promotion $promotion): void
    {
        if ($promotion->allow_walk_in_member) {
            return;
        }

        if ($this->isMemberAttached()) {
            return;
        }

        if ($this->checkSaleDetailsService->employee instanceof Employee) {
            return;
        }

        $saleMismatchMessage = 'Specified promotion is not allowed for the walk in member.';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function isMemberAttached(): bool
    {
        if ($this->checkSaleDetailsService->isMemberAttached()) {
            return true;
        }

        return $this->checkSaleDetailsService->hasMemberDetails();
    }

    public function checkEmployee(Promotion $promotion): void
    {
        $this->checkEmployeeAllowInPromotion($promotion);

        if (! $this->checkSaleDetailsService->employee instanceof Employee) {
            return;
        }

        if ($promotion->employeeGroups->isEmpty()) {
            return;
        }

        if (
            $this->checkSaleDetailsService->employee->group_id
            && $promotion->employeeGroups->firstWhere('id', $this->checkSaleDetailsService->employee->group_id)
        ) {
            return;
        }

        $saleMismatchMessage = 'Employees is not valid for the specified promotion.';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkEmployeeAllowInPromotion(Promotion $promotion): void
    {
        if ($promotion->allow_employee) {
            return;
        }

        if (! $this->checkSaleDetailsService->employee instanceof Employee) {
            return;
        }

        $saleMismatchMessage = 'Specified promotion is not allowed for the employees.';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkPromotionIsActive(Promotion $promotion): void
    {
        if (false === $promotion->status) {
            $saleMismatchMessage = 'Specified promotion is inactive.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkPromotionProductType(array $cartItem): void
    {
        /** @var Product $product */
        $product = $this->checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);

        if (! $this->checkSaleDetailsService->isBoxProductWithBoxProductIdAttached($product, $cartItem)) {
            return;
        }

        $saleMismatchMessage = 'Specified promotion is apply for bundle products.';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkPromotionLocations(Promotion $promotion): void
    {
        if ($promotion->locations->isEmpty()) {
            return;
        }

        if ($promotion->locations->firstWhere('id', $this->checkSaleDetailsService->location->id)) {
            return;
        }

        $saleMismatchMessage = 'Specified promotion is not available for the location ' . $this->checkSaleDetailsService->location->name;
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkItemWisePromotionRestrictions(Promotion $promotion, array $cartItem): void
    {
        if ($promotion->dream_price_applicable) {
            return;
        }

        if (! $this->checkSaleDetailsService->hasDreamPrice($cartItem)) {
            return;
        }

        $saleMismatchMessage = 'Specified promotion cannot be applied with the dream price';
        CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkCartWisePromotionRestrictions(Promotion $promotion): void
    {
        if ($promotion->dream_price_applicable) {
            return;
        }

        foreach ($this->checkSaleDetailsService->cartItems as $cartItem) {
            if ($this->checkSaleDetailsService->hasDreamPrice($cartItem)) {
                $saleMismatchMessage = 'Specified promotion cannot be applied with the dream price';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );

                return;
            }
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
            'Y-m-d H:i:s',
            $this->checkSaleDetailsService->saleData->happened_at
        );
        $happenedAt = $happenedAtFormat->format('Y-m-d');

        if ($promotion->start_date > $happenedAt || $promotion->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified promotion is available between ' . $promotion->start_date . ' and ' . $promotion->end_date . ' only. The sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkLimitByDayOfTheWeek(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->checkSaleDetailsService->saleData->happened_at
        );
        $happenedAtDay = $happenedAtFormat->format('w');

        if (! $promotion->weekly->firstWhere('week_day', $happenedAtDay)) {
            $saleMismatchMessage = 'Promotion is not allowed on this week day.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkLimitByDayOfTheMonth(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->checkSaleDetailsService->saleData->happened_at
        );
        $happenedAtDate = $happenedAtFormat->format('d');

        if (! $promotion->monthly->firstWhere('month_date', $happenedAtDate)) {
            $saleMismatchMessage = 'Promotion is not allowed on this day of the month.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkLimitByHourOfTheDay(Promotion $promotion): void
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->checkSaleDetailsService->saleData->happened_at
        );
        $happenedAt = $happenedAtFormat->format('Y-m-d');
        $happenedAtTime = $happenedAtFormat->format('H:i');

        if ($promotion->start_date !== $happenedAt) {
            $saleMismatchMessage = 'Promotion is not allowed on this date.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($promotion->start_time > $happenedAtTime || $promotion->end_time < $happenedAtTime) {
            $saleMismatchMessage = 'Promotion is not allowed at this time of the day.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getCartDiscountAmountFor(float $subtotal): array
    {
        $cartDiscount = [
            'cart_wide_discount' => 0,
            'voucher_discount' => 0,
            'total_discount' => 0,
            'price_override_discount' => 0,
            'cart_wide_loyalty_point_discount' => 0,
        ];

        if ($this->checkSaleDetailsService->hasCartPromotion()) {
            $promotion = $this->promotions->firstWhere(
                'id',
                $this->checkSaleDetailsService->saleData->cart_promotion_id
            );

            if ($promotion && in_array(
                $promotion->cart_wide_promotion_type_id,
                [CartWidePromotionTypes::AS_PER_AMOUNT->value,
                    CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                ])) {
                $subtotalForDiscount = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType(
                    $subtotal
                );
                $cartWidePromotionDiscount = 0;
                if ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_AMOUNT->value) {
                    $cartWideAsPerAmountPromotionService = resolve(CartWideAsPerAmountPromotionService::class);
                    $cartWidePromotionDiscount = $cartWideAsPerAmountPromotionService->getCartDiscountAmount(
                        $this->checkSaleDetailsService
                    );
                } elseif ($promotion->cart_wide_promotion_type_id === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                    $cartWideAsPerPaymentTypePromotionService = resolve(
                        CartWideAsPerPaymentTypePromotionService::class
                    );
                    $cartWidePromotionDiscount = $cartWideAsPerPaymentTypePromotionService->getCartDiscountAmount(
                        $this->checkSaleDetailsService
                    );
                }

                $cartDiscount['cart_wide_discount'] = $cartWidePromotionDiscount;
                $cartDiscount['total_discount'] = $cartWidePromotionDiscount;
                $subtotal -= $cartWidePromotionDiscount;
            }
        }

        if ($this->checkSaleDetailsService->hasVoucher() && $this->voucher instanceof Voucher) {
            $subtotalForDiscount = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType($subtotal);

            $voucherDiscountService = resolve(VoucherDiscountService::class);

            $voucherDiscount = $voucherDiscountService->getDiscountAmount($this->checkSaleDetailsService);

            $cartDiscount['voucher_discount'] = $voucherDiscount;
            $cartDiscount['total_discount'] += $voucherDiscount;
            $subtotal -= $voucherDiscount;
        }

        if ($this->checkSaleDetailsService->hasPriceOverrideForCart()) {
            $subtotalForDiscount = $this->checkSaleDetailsService->getCartSubtotalByDiscountApplicableType($subtotal);

            $salePriceOverrideService = resolve(SalePriceOverrideService::class);

            $priceOverrideDiscount = $salePriceOverrideService->getDiscountAmount(
                $subtotalForDiscount,
                (float) $this->checkSaleDetailsService->saleData->cart_price_override_amount,
                $this->checkSaleDetailsService->saleData->cart_price_override_discount_amount
            );

            $cartDiscount['price_override_discount'] = $priceOverrideDiscount;
            $cartDiscount['total_discount'] += $priceOverrideDiscount;
            $subtotal -= $priceOverrideDiscount;
        }

        if ($this->checkSaleDetailsService->hasLoyaltyPointsForCart()) {
            $loyaltyPointDiscount = $this->checkSaleDetailsService->saleData->cart_loyalty_point_amount;
            $cartDiscount['cart_wide_loyalty_point_discount'] = $loyaltyPointDiscount;
            $cartDiscount['total_discount'] += $loyaltyPointDiscount;
            $subtotal -= $loyaltyPointDiscount;
        }

        return $cartDiscount;
    }

    public function getItemCartDiscountAmount(float $cartSubtotal, float $itemSubtotal, array $item): float
    {
        $cartDiscount = $this->getCartDiscountAmountFor($cartSubtotal);
        if (! $this->isCartDiscountItemSequenceInAllItems()) {
            if ($cartSubtotal > 0) {
                return CommonFunctions::numberFormat($itemSubtotal * $cartDiscount['total_discount'] / $cartSubtotal);
            }

            return 0.00;
        }

        return $this->getCalculateItemCartDiscountAmount($item, $cartDiscount['total_discount'], $cartSubtotal);
    }

    public function getCalculateItemCartDiscountAmount(
        array $item,
        float $cartDiscount,
        float $cartSubtotal,
    ): float {
        $cartItems = $this->checkSaleDetailsService->cartItems
                    ->sortBy('cart_discount_item_sequence')
                    ->values();

        $lastKey = $cartItems->keys()->last();

        $itemTotalDiscount = 0.00;
        foreach ($cartItems as $cartItemKey => $cartItem) {
            if ($cartItemKey === $lastKey) {
                return CommonFunctions::numberFormat($cartDiscount - $itemTotalDiscount);
            }

            $itemTotal = $this->getItemSubTotalAfterItemDiscount($cartItem);

            $itemDiscount = CommonFunctions::numberFormat($itemTotal * $cartDiscount / $cartSubtotal);

            if ($itemDiscount <= 0) {
                $itemDiscount = 0.00;
            }

            $itemTotalDiscount += $itemDiscount;

            if ($this->isCartDiscountReturn($cartItem, $item)) {
                return $itemDiscount;
            }
        }

        return 0.00;
    }

    public function getItemSubTotalAfterItemDiscount(array $cartItem): float
    {
        $itemDiscounts = $this->getItemDiscountAmountFor($cartItem);
        $itemTotalDiscount = $itemDiscounts['total_discount'];

        $itemSubTotal = $this->checkSaleDetailsService->getItemSubtotal($cartItem);

        return CommonFunctions::numberFormat($itemSubTotal - $itemTotalDiscount);
    }

    public function isCartDiscountItemSequenceInAllItems(): bool
    {
        return $this->checkSaleDetailsService->cartItems->where('cart_discount_item_sequence', '>', 0)->isNotEmpty();
    }

    public function isCartDiscountItemSequence(array $cartItem): bool
    {
        if (! array_key_exists('cart_discount_item_sequence', $cartItem)) {
            return false;
        }

        return (bool) $cartItem['cart_discount_item_sequence'];
    }

    public function isCartDiscountReturn(array $cartItem, array $item): bool
    {
        if ($this->isCartDiscountItemSequence($item) && $this->isCartDiscountItemSequence($cartItem)) {
            return $this->matchItemByCartDiscountItemSequence($cartItem, $item);
        }

        return $cartItem['id'] === $item['id'];
    }

    public function matchItemByCartDiscountItemSequence(array $cartItem, array $item): bool
    {
        return $cartItem['cart_discount_item_sequence'] === $item['cart_discount_item_sequence'];
    }

    public function getTotalItemDiscountAmount(): float
    {
        $totalItemDiscount = 0.00;
        foreach ($this->checkSaleDetailsService->cartItems as $cartItem) {
            $itemDiscounts = $this->getItemDiscountAmountFor($cartItem);
            $totalItemDiscount += $itemDiscounts['total_discount'];
        }

        return $totalItemDiscount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getItemDiscountAmountFor(array $cartItem): array
    {
        $discounts = [
            'happy_hour_discount' => 0.00,
            'complimentary_item_discount' => 0.00,
            'loyalty_point_item_discount' => 0.00,
            'price_override_discount' => 0.00,
            'dream_price_discount' => 0.00,
            'item_wise_discount' => 0.00,
            'total_discount' => 0.00,
        ];

        if ($this->checkSaleDetailsService->hasHappyHourDiscount($cartItem)) {
            $happyHourDiscountSaleService = resolve(HappyHourDiscountSaleService::class);
            $discountAmount = $happyHourDiscountSaleService->getItemDiscountAmount($cartItem);
            $discounts['happy_hour_discount'] = $discountAmount;
            $discounts['total_discount'] += $discountAmount;

            return $discounts;
        }

        if ($this->checkSaleDetailsService->hasDreamPrice($cartItem)) {
            $dreamPriceService = resolve(DreamPriceService::class);
            $dreamPriceDiscountAmount = $dreamPriceService->getDiscountFor($cartItem);
            $discounts['dream_price_discount'] = $dreamPriceDiscountAmount;
            $discounts['total_discount'] += $dreamPriceDiscountAmount;
        }

        if ($this->checkSaleDetailsService->hasComplimentaryItem($cartItem)) {
            if (0.00 !== $discounts['dream_price_discount']) {
                $cartItem['price'] = $cartItem['dream_price_amount'];
            }

            if (! array_key_exists('complimentary_item_discount', $cartItem)) {
                $cartItem['complimentary_item_discount'] = 0.0;
                $saleMismatchMessage = 'Complimentary item discount amount not specified.';
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            $complimentaryItemService = resolve(ComplimentaryItemService::class);
            $itemSubtotal = $this->checkSaleDetailsService->getItemSubtotal($cartItem);
            $discountAmount = $complimentaryItemService->getItemDiscountAmount($itemSubtotal);
            $cartComplimentaryDiscountAmount = (float) $cartItem['complimentary_item_discount'];
            $discounts['complimentary_item_discount'] = $cartComplimentaryDiscountAmount;
            $discounts['total_discount'] += $discountAmount;

            if (! CommonFunctions::compareFloatNumbers($cartComplimentaryDiscountAmount, $discountAmount)) {
                $saleMismatchMessage = 'Provided complimentary item discount does not match with calculated amount.\nExpected: ' . CommonFunctions::numberFormat(
                    $discountAmount
                ) . '\\n' .
                        'Received: ' . CommonFunctions::numberFormat($discounts['complimentary_item_discount']);
                CommonFunctions::addMismatchOrAbort(
                    $this->checkSaleDetailsService->saleMismatches,
                    $saleMismatchMessage
                );
            }

            return $discounts;
        }

        if ($this->checkSaleDetailsService->hasProductLoyaltyPoints($cartItem)) {
            if (0.00 !== $discounts['dream_price_discount']) {
                $cartItem['price'] = $cartItem['dream_price_amount'];
            }

            if (! array_key_exists('loyalty_point_item_discount', $cartItem)) {
                $cartItem['loyalty_point_item_discount'] = 0.0;
            }

            $complimentaryItemService = resolve(ComplimentaryItemService::class);
            $discountAmount = (float) $cartItem['loyalty_point_item_discount'];
            $discounts['loyalty_point_item_discount'] = $discountAmount;
            $discounts['total_discount'] += $discountAmount;

            return $discounts;
        }

        if ($this->checkSaleDetailsService->hasItemPromotion($cartItem)) {
            $promotion = $this->promotions->firstWhere('id', $cartItem['promotion_id']);
            $promotionClass = ItemWisePromotionTypes::getPromotionClass($promotion->item_wise_promotion_type_id);
            if ($promotionClass instanceof SalePromotionInterface) {
                $itemDiscount = $promotionClass->getItemDiscountAmount($cartItem);

                $discounts['item_wise_discount'] = $itemDiscount;
                $discounts['total_discount'] += $itemDiscount;
            }
        }

        if (! $this->checkSaleDetailsService->hasPriceOverride($cartItem)) {
            return $discounts;
        }

        $saleItemPriceOverrideService = resolve(SaleItemPriceOverrideService::class);
        $allowedPriceOverrideDiscountAmount = $saleItemPriceOverrideService->getItemDiscountAmount(
            $this->checkSaleDetailsService,
            $cartItem
        );
        $discounts['price_override_discount'] = $allowedPriceOverrideDiscountAmount;
        $discounts['total_discount'] += $allowedPriceOverrideDiscountAmount;

        return $discounts;
    }

    public function applyDreamPriceOn(array $cartItem): float
    {
        $itemTotal = $this->checkSaleDetailsService->getItemSubtotal($cartItem);
        if ($this->checkSaleDetailsService->hasDreamPrice($cartItem)) {
            $dreamPriceService = resolve(DreamPriceService::class);
            $itemTotal -= $dreamPriceService->getDiscountFor($cartItem);

            $cartItem['price'] = $cartItem['dream_price_amount'];
        }

        return $itemTotal;
    }

    public function applyDreamPriceAndItemPromotionOn(array $cartItem): float
    {
        $itemTotal = $this->checkSaleDetailsService->getItemSubtotal($cartItem);

        if ($this->checkSaleDetailsService->hasDreamPrice($cartItem)) {
            $dreamPriceService = resolve(DreamPriceService::class);
            $itemTotal -= $dreamPriceService->getDiscountFor($cartItem);

            $cartItem['price'] = $cartItem['dream_price_amount'];
        }

        if ($this->checkSaleDetailsService->hasItemPromotion($cartItem)) {
            $promotion = $this->promotions->firstWhere('id', $cartItem['promotion_id']);
            $promotionClass = ItemWisePromotionTypes::getPromotionClass($promotion->item_wise_promotion_type_id);
            if ($promotionClass instanceof SalePromotionInterface) {
                $itemTotal -= $promotionClass->getItemDiscountAmount($cartItem);
            }
        }

        return $itemTotal;
    }

    public function hasGroupId(array $cartItem): bool
    {
        return array_key_exists('group_id', $cartItem) && $cartItem['group_id'];
    }

    public function groupItemsSubtotalWithApplyDreamPriceAndPriceOverride(array $cartItem): float
    {
        $subtotal = 0.00;

        if ($this->hasGroupId($cartItem)) {
            $cartItems = $this->checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
                ->where('promotion_id', $cartItem['promotion_id'])
                ->where('item_discount_amount', '>', 0);

            foreach ($cartItems as $cartItem) {
                $itemTotal = $this->applyDreamPriceOn($cartItem);
                $subtotal += $this->checkSaleDetailsService->getItemSubtotalByDiscountApplicableType(
                    $itemTotal,
                    $cartItem
                );
            }
        }

        return $subtotal;
    }

    public function buyItemsSubtotalWithApplyDreamPriceAndPriceOverride(array $cartItem): float
    {
        $subtotal = 0.00;
        if ($this->hasGroupId($cartItem)) {
            $cartItems = $this->checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
                ->where('promotion_id', $cartItem['promotion_id'])
                ->where('item_discount_amount', '<=', 0);

            foreach ($cartItems as $cartItem) {
                $itemTotal = $this->applyDreamPriceOn($cartItem);
                $subtotal += $this->checkSaleDetailsService->getItemSubtotalByDiscountApplicableType(
                    $itemTotal,
                    $cartItem
                );
            }
        }

        return $subtotal;
    }

    public function groupItems(Collection $cartItems, array $cartItem): Collection
    {
        if ($this->hasGroupId($cartItem)) {
            return $cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id']);
        }

        return collect([]);
    }

    public function getGroupItems(Collection $cartItems, array $cartItem): Collection
    {
        if ($this->hasGroupId($cartItem)) {
            return $cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);
        }

        return collect([]);
    }

    public function getBuyGroupItems(Collection $cartItems, array $cartItem): Collection
    {
        if ($this->hasGroupId($cartItem)) {
            return $cartItems->where('group_id', $cartItem['group_id'])
                    ->where('promotion_id', $cartItem['promotion_id'])
                    ->where('item_discount_amount', '<=', 0);
        }

        return collect([]);
    }

    /**
     * @return mixed[]
     */
    private function getDirectorIds(): array
    {
        return $this->checkSaleDetailsService->cartItems->pluck('director_id')
            ->unique()
            ->filter()
            ->toArray();
    }

    /**
     * @return mixed[]
     */
    private function getStoreManagerIds(): array
    {
        return $this->checkSaleDetailsService->cartItems->pluck('store_manager_id')
            ->unique()
            ->filter()
            ->toArray();
    }
}
