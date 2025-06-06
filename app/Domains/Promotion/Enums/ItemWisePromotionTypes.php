<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Domains\Promotion\Interfaces\SalePromotionInterface;
use App\Domains\Promotion\Services\AsPerAmountGetOffOnOthersPromotionService;
use App\Domains\Promotion\Services\AsPerAmountLimitedToBrandsPromotionService;
use App\Domains\Promotion\Services\AsPerAmountLimitedToPricePromotionService;
use App\Domains\Promotion\Services\BundleBuyPromotionService;
use App\Domains\Promotion\Services\BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService;
use App\Domains\Promotion\Services\BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService;
use App\Domains\Promotion\Services\BuyThreeGetOnePromotionService;
use App\Domains\Promotion\Services\BuyTwoAndGetOneQuantityAtRM1PromotionService;
use App\Domains\Promotion\Services\BuyTwoGetFiftyPercentageOffOnOthersPromotionService;
use App\Domains\Promotion\Services\BuyTwoGetRMFiftyOffOnOthersPromotionService;
use App\Domains\Promotion\Services\CheapestFreePromotionService;
use App\Domains\Promotion\Services\FlatDiscountForNextItemPromotionService;
use App\Domains\Promotion\Services\GiftWithPurchasePromotionService;
use App\Domains\Promotion\Services\LimitedToBrandsPromotionService;
use App\Domains\Promotion\Services\LimitedToCategoriesPromotionService;
use App\Domains\Promotion\Services\LimitedToProductCollectionPromotionService;
use App\Domains\Promotion\Services\LimitedToProductsPromotionService;
use App\Domains\Promotion\Services\LimitedToTagsPromotionService;
use App\Domains\Promotion\Services\PercentageDiscountForNextItemPromotionService;
use App\Http\Traits\PrepareEnumDataMethods;

enum ItemWisePromotionTypes: int
{
    use PrepareEnumDataMethods;

    case LIMITED_TO_PRODUCTS = 1;
    case LIMITED_TO_CATEGORIES = 2;
    case BUY_3_GET_1 = 3;
    case BUY_2_GET_50_OFF_ON_OTHERS = 4;
    case BUY_ANY_3_OR_MORE_AND_GET_30_OFF = 5;
    case CHEAPEST_FREE = 6;
    case BUNDLE_BUY = 7;
    case GIFT_WITH_PURCHASE = 8;
    case BUY_2_GET_RM_50_OFF_ON_OTHERS = 9;
    case BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF = 10;
    case BUY_2_AND_GET_1_QUANTITY_AT_RM1 = 11;
    case LIMITED_TO_BRANDS = 12;
    case AS_PER_AMOUNT_LIMITED_TO_BRANDS = 13;
    case AS_PER_AMOUNT_GET_OFF_ON_OTHERS = 14;
    case AS_PER_AMOUNT_LIMITED_TO_PRICE = 15;
    case LIMITED_TO_TAGS = 16;
    case PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM = 17;
    case FLAT_DISCOUNT_FOR_NEXT_ITEM = 18;
    case LIMITED_TO_PRODUCT_COLLECTION = 19;

    public static function getPromotionClass(?int $promotionTypeId): ?SalePromotionInterface
    {
        if ($promotionTypeId === self::LIMITED_TO_CATEGORIES->value) {
            return resolve(LimitedToCategoriesPromotionService::class);
        }

        if ($promotionTypeId === self::LIMITED_TO_PRODUCTS->value) {
            return resolve(LimitedToProductsPromotionService::class);
        }

        if ($promotionTypeId === self::LIMITED_TO_BRANDS->value) {
            return resolve(LimitedToBrandsPromotionService::class);
        }

        if ($promotionTypeId === self::LIMITED_TO_TAGS->value) {
            return resolve(LimitedToTagsPromotionService::class);
        }

        if ($promotionTypeId === self::LIMITED_TO_PRODUCT_COLLECTION->value) {
            return resolve(LimitedToProductCollectionPromotionService::class);
        }

        if ($promotionTypeId === self::BUY_3_GET_1->value) {
            return resolve(BuyThreeGetOnePromotionService::class);
        }

        if ($promotionTypeId === self::BUNDLE_BUY->value) {
            return resolve(BundleBuyPromotionService::class);
        }

        if ($promotionTypeId === self::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value) {
            return resolve(BuyTwoAndGetOneQuantityAtRM1PromotionService::class);
        }

        if ($promotionTypeId === self::CHEAPEST_FREE->value) {
            return resolve(CheapestFreePromotionService::class);
        }

        if ($promotionTypeId === self::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value) {
            return resolve(BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService::class);
        }

        if ($promotionTypeId === self::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value) {
            return resolve(PercentageDiscountForNextItemPromotionService::class);
        }

        if ($promotionTypeId === self::FLAT_DISCOUNT_FOR_NEXT_ITEM->value) {
            return resolve(FlatDiscountForNextItemPromotionService::class);
        }

        if ($promotionTypeId === self::BUY_2_GET_50_OFF_ON_OTHERS->value) {
            return resolve(BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class);
        }

        if ($promotionTypeId === self::BUY_2_GET_RM_50_OFF_ON_OTHERS->value) {
            return resolve(BuyTwoGetRMFiftyOffOnOthersPromotionService::class);
        }

        if ($promotionTypeId === self::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value) {
            return resolve(BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService::class);
        }

        if ($promotionTypeId === self::GIFT_WITH_PURCHASE->value) {
            return resolve(GiftWithPurchasePromotionService::class);
        }

        if ($promotionTypeId === self::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value) {
            return resolve(AsPerAmountLimitedToBrandsPromotionService::class);
        }

        if ($promotionTypeId === self::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value) {
            return resolve(AsPerAmountGetOffOnOthersPromotionService::class);
        }

        if ($promotionTypeId === self::AS_PER_AMOUNT_LIMITED_TO_PRICE->value) {
            return resolve(AsPerAmountLimitedToPricePromotionService::class);
        }

        return null;
    }
}
