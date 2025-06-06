<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionTier;
use App\Models\PromotionWeekDay;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(
        int $companyId,
        Collection $products,
        Collection $categories,
        Collection $brands,
        Collection $locations,
        Collection $productCollections
    ): void {
        $this->cartWideAsPerAmountWithNoLimit($companyId, $locations);

        $this->giftWithPurchaseWithNoLimit($companyId, $locations, $products);

        $this->limitedToProductsWithLimitedByDates($companyId, $locations, $products);

        $this->limitedToCategoriesWithLimitByDayOfTheWeek($companyId, $locations, $categories);

        $this->limitedToBrandsWithNoLimit($companyId, $locations, $brands);

        $this->limitedToProductCollectionWithNoLimit($companyId, $locations, $productCollections);

        $this->buyThreeGetOneWithLimitByHourOfTheDay($companyId, $locations, $products);

        $this->buyTwoGetFiftyOffOnOthersWithLimitByDayOfTheMonth($companyId, $locations, $products);

        $this->cheapestOfferWithLimitedByDates($companyId, $locations, $products);

        $this->bundleBuyWithLimitedDate($companyId, $locations, $products);

        $this->buyTwoAndGetOneQuantityAtRmOneWithLimitedDate($companyId, $locations, $products);

        $this->buyAnyThreeOrMoreAndGetThirtyPercentOff($companyId, $locations, $products);

        $this->percentageDiscountForNextItem($companyId, $locations, $products);

        $this->flatDiscountForNextItem($companyId, $locations, $products);

        $this->buyAnyThreeOrMoreAndGetRMThirtyFlatOff($companyId, $locations, $products);

        $this->buyTwoGetRMFiftyOffOnOthersWithLimitByDayOfTheMonth($companyId, $locations, $products);

        $this->asPerAmountGetOffOnOthers($companyId, $locations, $products);

        $this->asPerAmountLimitedToBrands($companyId, $locations, $brands);

        $this->asPerAmountLimitedToPrice($companyId, $locations);
    }

    private function cartWideAsPerAmountWithNoLimit(int $companyId, Collection $locations): void
    {
        $promotionCartWideAndNoLimit = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Cart Wide & automatic',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
                'item_wise_promotion_type_id' => null,
            ]);

        PromotionTier::factory()->create([
            'promotion_id' => $promotionCartWideAndNoLimit->id,
            'buy_value' => 50,
            'get_value' => 1,
        ]);
    }

    private function giftWithPurchaseWithNoLimit(int $companyId, Collection $locations, Collection $products): void
    {
        $giftWithPurchase = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Gift with purchase',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
                'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
                'discount_type_id' => null,
                'percentage' => null,
                'flat_amount' => null,
                'cart_wide_promotion_type_id' => null,
            ]);

        PromotionTier::factory()->create([
            'promotion_id' => $giftWithPurchase->id,
            'buy_value' => 50,
            'get_value' => 1,
        ]);

        $giftWithPurchase->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );
    }

    private function limitedToProductsWithLimitedByDates(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $limitedToProducts = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Limited to Product',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'discount_type_id' => DiscountTypes::FLAT,
                'percentage' => null,
                'flat_amount' => 50,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $limitedToProducts->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );
    }

    private function limitedToCategoriesWithLimitByDayOfTheWeek(
        int $companyId,
        Collection $locations,
        Collection $categories
    ): void {
        $limitedCategories = Promotion::factory()
            ->hasAttached($locations)
            ->hasAttached($categories)
            ->create([
                'name' => 'Limited to Categories',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE,
                'percentage' => 2,
            ]);

        for ($i = 0; $i < 3; $i++) {
            PromotionWeekDay::create([
                'promotion_id' => $limitedCategories->id,
                'week_day' => $i,
            ]);
        }
    }

    private function limitedToBrandsWithNoLimit(int $companyId, Collection $locations, Collection $brands): void
    {
        Promotion::factory()
            ->hasAttached($locations)
            ->hasAttached($brands)
            ->create([
                'name' => 'Limited to Brands',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_BRANDS->value,
                'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE,
                'percentage' => 2,
                'status' => true,
            ]);
    }

    private function limitedToProductCollectionWithNoLimit(
        int $companyId,
        Collection $locations,
        Collection $productCollections
    ): void {
        Promotion::factory()
            ->hasAttached($locations)
            ->hasAttached($productCollections)
            ->create([
                'name' => 'Limited to Product Collection',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value,
                'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE,
                'percentage' => 2,
                'status' => true,
            ]);
    }

    private function buyThreeGetOneWithLimitByHourOfTheDay(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $carbon = Carbon::now();
        $buy3Get1 = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Buy 6 Get 2',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_3_GET_1->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value,
                'start_date' => $carbon->toDateString(),
                'start_time' => $carbon->toTimeString(),
                'end_time' => $carbon->toTimeString(),
            ]);

        $buy3Get1->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::BUY_PRODUCT->value,
            ]
        );

        $buy3Get1->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::GET_PRODUCT->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $buy3Get1->id,
            'buy_value' => 6,
            'get_value' => 2,
        ]);
    }

    private function buyTwoGetFiftyOffOnOthersWithLimitByDayOfTheMonth(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $promotionBuy2Get50OffOnOthers = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Buy Two Get Fifty Off On Others',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            ]);

        for ($i = 0; $i < 4; $i++) {
            PromotionMonthDate::factory()->create([
                'promotion_id' => $promotionBuy2Get50OffOnOthers->id,
                'month_date' => $i,
            ]);
        }

        $promotionBuy2Get50OffOnOthers->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::BUY_PRODUCT->value,
            ]
        );

        $promotionBuy2Get50OffOnOthers->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::GET_PRODUCT->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $promotionBuy2Get50OffOnOthers->id,
            'buy_value' => 5,
            'get_value' => 1,
        ]);
    }

    private function cheapestOfferWithLimitedByDates(int $companyId, Collection $locations, Collection $products): void
    {
        $cheapestOffer = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Cheapest free',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::CHEAPEST_FREE->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $cheapestOffer->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $cheapestOffer->id,
            'buy_value' => 3,
            'get_value' => 1,
        ]);
    }

    private function bundleBuyWithLimitedDate(int $companyId, Collection $locations, Collection $products): void
    {
        $bundleBuy = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Bundle Free',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUNDLE_BUY->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $bundleBuy->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $bundleBuy->id,
            'buy_value' => 2,
            'get_value' => 1,
        ]);
    }

    private function buyTwoAndGetOneQuantityAtRmOneWithLimitedDate(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $buyTwoAndGetOneQuantityAtRmOne = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Buy Two And Get One Quantity At RM1',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $buyTwoAndGetOneQuantityAtRmOne->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $buyTwoAndGetOneQuantityAtRmOne->id,
            'buy_value' => 2,
            'get_value' => 1,
            'get_quantity' => 1,
        ]);
    }

    private function buyAnyThreeOrMoreAndGetThirtyPercentOff(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $buyAnyThreeOrMoreAndGetThirtyPercentOff = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Buy any three or more and get Thirty % off',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $buyAnyThreeOrMoreAndGetThirtyPercentOff->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $buyAnyThreeOrMoreAndGetThirtyPercentOff->id,
            'buy_value' => 3,
            'get_value' => 1,
        ]);
    }

    private function percentageDiscountForNextItem(int $companyId, Collection $locations, Collection $products): void
    {
        $percentageDiscountForNextItem = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Percentage Discount For Next Item',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $percentageDiscountForNextItem->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $percentageDiscountForNextItem->id,
            'buy_value' => 3,
            'get_value' => 1,
        ]);
    }

    private function flatDiscountForNextItem(int $companyId, Collection $locations, Collection $products): void
    {
        $flatDiscountForNextItem = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Flat Discount For Next Item',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $flatDiscountForNextItem->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $flatDiscountForNextItem->id,
            'buy_value' => 3,
            'get_value' => 1,
        ]);
    }

    private function buyAnyThreeOrMoreAndGetRMThirtyFlatOff(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $buyAnyThreeOrMoreAndGetRMThirtyFlatOff = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Buy any three or more and get RM30 flat off',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::tomorrow(),
            ]);

        $buyAnyThreeOrMoreAndGetRMThirtyFlatOff->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $buyAnyThreeOrMoreAndGetRMThirtyFlatOff->id,
            'buy_value' => 3,
            'get_value' => 30,
        ]);
    }

    private function buyTwoGetRMFiftyOffOnOthersWithLimitByDayOfTheMonth(
        int $companyId,
        Collection $locations,
        Collection $products
    ): void {
        $promotionBuy2GetRM50OffOnOthers = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'Buy Two Get RM50 Off On Others',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
            ]);

        for ($i = 0; $i < 4; $i++) {
            PromotionMonthDate::factory()->create([
                'promotion_id' => $promotionBuy2GetRM50OffOnOthers->id,
                'month_date' => $i,
            ]);
        }

        $promotionBuy2GetRM50OffOnOthers->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::BUY_PRODUCT->value,
            ]
        );

        $promotionBuy2GetRM50OffOnOthers->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::GET_PRODUCT->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $promotionBuy2GetRM50OffOnOthers->id,
            'buy_value' => 2,
            'get_value' => 50,
        ]);
    }

    private function asPerAmountGetOffOnOthers(int $companyId, Collection $locations, Collection $products): void
    {
        $asPerAmountGetOffOnOthers = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'As Per Amount Get Off On Others',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
            ]);

        $asPerAmountGetOffOnOthers->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::BUY_PRODUCT->value,
            ]
        );

        $asPerAmountGetOffOnOthers->uploadedProducts()->syncWithPivotValues(
            $products->pluck('id')->take(3),
            [
                'type' => ProductUploadTypes::GET_PRODUCT->value,
            ]
        );

        PromotionTier::factory()->create([
            'promotion_id' => $asPerAmountGetOffOnOthers->id,
            'buy_value' => 100,
            'get_value' => 10,
            'max_value' => 10,
        ]);
    }

    private function asPerAmountLimitedToBrands(int $companyId, Collection $locations, Collection $brands): void
    {
        $promotionAsPerAmountLimitedToBrands = Promotion::factory()
            ->hasAttached($locations)
            ->hasAttached($brands)
            ->create([
                'name' => 'As Per Amount Limited To Brands',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
            ]);

        PromotionTier::factory()->create([
            'promotion_id' => $promotionAsPerAmountLimitedToBrands->id,
            'buy_value' => 100,
            'get_value' => 10,
        ]);
    }

    private function asPerAmountLimitedToPrice(int $companyId, Collection $locations): void
    {
        $promotionAsPerAmountLimitedToPrice = Promotion::factory()
            ->hasAttached($locations)
            ->create([
                'name' => 'As Per Amount Limited To Price',
                'company_id' => $companyId,
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'cart_wide_promotion_type_id' => null,
                'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
            ]);

        PromotionTier::factory()->create([
            'promotion_id' => $promotionAsPerAmountLimitedToPrice->id,
            'buy_value' => 10,
            'max_value' => 20,
            'get_value' => 10,
        ]);
    }
}
