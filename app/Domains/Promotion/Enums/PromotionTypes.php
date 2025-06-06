<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Domains\Common\Enums\DiscountTypes;
use App\Http\Traits\PrepareEnumDataMethods;

enum PromotionTypes: int
{
    use PrepareEnumDataMethods;

    case CART_WIDE_AUTOMATIC_PERCENTAGE = 1;
    case CART_WIDE_AUTOMATIC_FLAT = 2;
    case ITEM_WISE_GIFT_WITH_PURCHASE = 3;
    case ITEM_WISE_LIMITED_TO_PRODUCTS_PERCENTAGE = 4;
    case ITEM_WISE_LIMITED_TO_PRODUCTS_FLAT = 5;
    case ITEM_WISE_LIMITED_TO_CATEGORIES_PERCENTAGE = 6;
    case ITEM_WISE_LIMITED_TO_CATEGORIES_FLAT = 7;
    case ITEM_WISE_BUY_3_GET_1 = 8;
    case ITEM_WISE_BUY_2_GET_50_OFF_ON_OTHERS = 9;
    case ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_30_OFF = 10;
    case ITEM_WISE_CHEAPEST_FREE = 11;
    case ITEM_WISE_BUNDLE_BUY = 12;
    case ITEM_WISE_BUY_2_GET_RM_50_OFF_ON_OTHERS = 13;
    case ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF = 14;
    case ITEM_WISE_BUY_2_AND_GET_1_QUANTITY_AT_RM1 = 15;
    case ITEM_WISE_LIMITED_TO_BRANDS_PERCENTAGE = 16;
    case ITEM_WISE_LIMITED_TO_BRANDS_FLAT = 17;
    case ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_PERCENTAGE = 18;
    case ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_FLAT = 19;
    case ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_PERCENTAGE = 20;
    case ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_FLAT = 21;
    case ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_PERCENTAGE = 22;
    case ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT = 23;
    case ITEM_WISE_LIMITED_TO_TAGS_PERCENTAGE = 24;
    case ITEM_WISE_LIMITED_TO_TAGS_FLAT = 25;
    case ITEM_WISE_PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM = 26;
    case ITEM_WISE_FLAT_DISCOUNT_FOR_NEXT_ITEM = 27;
    case ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_PERCENTAGE = 28;
    case ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_FLAT = 29;

    case CART_WIDE_AS_PER_AMOUNT_PERCENTAGE = 30;
    case CART_WIDE_AS_PER_AMOUNT_FLAT = 31;
    case CART_WIDE_AS_PER_PAYMENT_TYPE_PERCENTAGE = 32;
    case CART_WIDE_AS_PER_PAYMENT_TYPE_FLAT = 33;

    public static function getPromotionTypeCondition(int $promotionTypeId): array
    {
        $conditions = [
            PromotionTypes::CART_WIDE_AUTOMATIC_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::CART_WIDE_AUTOMATIC_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_GIFT_WITH_PURCHASE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_BUY_3_GET_1->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_3_GET_1->value,
            ],
            PromotionTypes::ITEM_WISE_BUY_2_GET_50_OFF_ON_OTHERS->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_FLAT_DISCOUNT_FOR_NEXT_ITEM->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_BUY_2_GET_RM_50_OFF_ON_OTHERS->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_CHEAPEST_FREE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::CHEAPEST_FREE->value,
            ],
            PromotionTypes::ITEM_WISE_BUNDLE_BUY->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUNDLE_BUY->value,
            ],
            PromotionTypes::ITEM_WISE_BUY_2_AND_GET_1_QUANTITY_AT_RM1->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_BRANDS->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_BRANDS->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_TAGS->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_TAGS->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
                'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::CART_WIDE_AS_PER_AMOUNT_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::CART_WIDE_AS_PER_AMOUNT_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
            PromotionTypes::CART_WIDE_AS_PER_PAYMENT_TYPE_PERCENTAGE->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                'discount_type_id' => DiscountTypes::PERCENTAGE->value,
            ],
            PromotionTypes::CART_WIDE_AS_PER_PAYMENT_TYPE_FLAT->value => [
                'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
                'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                'discount_type_id' => DiscountTypes::FLAT->value,
            ],
        ];

        return $conditions[$promotionTypeId];
    }
}
