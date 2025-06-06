<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataPreparer;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTypes;
use App\Models\Model;
use App\Models\Promotion;
use App\Models\SaleCashback;
use Illuminate\Support\Collection;

class SaleDataPreparer
{
    public static function getPromotionType(Model $discountable): string
    {
        if (! $discountable instanceof Promotion) {
            return '';
        }

        if ($discountable->promotion_applicable_type_id === PromotionApplicableTypes::CART_WIDE->value) {
            if ($discountable->discount_type_id === DiscountTypes::PERCENTAGE->value) {
                return PromotionTypes::CART_WIDE_AUTOMATIC_PERCENTAGE->name;
            }

            if ($discountable->discount_type_id === DiscountTypes::FLAT->value) {
                return PromotionTypes::CART_WIDE_AUTOMATIC_FLAT->name;
            }
        }

        if (
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_TAGS->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value
        ) {
            if ($discountable->discount_type_id === DiscountTypes::PERCENTAGE->value) {
                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_PERCENTAGE->name;
                }

                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_PERCENTAGE->name;
                }

                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_PERCENTAGE->name;
                }

                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_TAGS->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_PERCENTAGE->name;
                }

                return PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_PERCENTAGE->name;
            }

            if ($discountable->discount_type_id === DiscountTypes::FLAT->value) {
                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_FLAT->name;
                }

                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_FLAT->name;
                }

                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_FLAT->name;
                }

                if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_TAGS->value) {
                    return PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_FLAT->name;
                }

                return PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_FLAT->name;
            }
        }

        if (
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value
        ) {
            return $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value ?
                PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_30_OFF->name :
                PromotionTypes::ITEM_WISE_BUY_2_GET_50_OFF_ON_OTHERS->name;
        }

        if (
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value
        ) {
            return PromotionTypes::ITEM_WISE_PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->name;
        }

        if (
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value
        ) {
            return PromotionTypes::ITEM_WISE_FLAT_DISCOUNT_FOR_NEXT_ITEM->name;
        }

        if (
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value
        ) {
            return $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value ?
                PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->name :
                PromotionTypes::ITEM_WISE_BUY_2_GET_RM_50_OFF_ON_OTHERS->name;
        }

        if (
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_3_GET_1->value ||
            $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::CHEAPEST_FREE->value
        ) {
            return $discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_3_GET_1->value ?
                PromotionTypes::ITEM_WISE_BUY_3_GET_1->name :
                PromotionTypes::ITEM_WISE_CHEAPEST_FREE->name;
        }

        if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value) {
            return PromotionTypes::ITEM_WISE_GIFT_WITH_PURCHASE->name;
        }

        if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value) {
            return PromotionTypes::ITEM_WISE_BUY_2_AND_GET_1_QUANTITY_AT_RM1->name;
        }

        if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value) {
            if ($discountable->discount_type_id === DiscountTypes::PERCENTAGE->value) {
                return PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_PERCENTAGE->name;
            }

            if ($discountable->discount_type_id === DiscountTypes::FLAT->value) {
                return PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_FLAT->name;
            }
        }

        if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value) {
            if ($discountable->discount_type_id === DiscountTypes::PERCENTAGE->value) {
                return PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_PERCENTAGE->name;
            }

            if ($discountable->discount_type_id === DiscountTypes::FLAT->value) {
                return PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT->name;
            }
        }

        if ($discountable->item_wise_promotion_type_id === ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value) {
            if ($discountable->discount_type_id === DiscountTypes::PERCENTAGE->value) {
                return PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_PERCENTAGE->name;
            }

            if ($discountable->discount_type_id === DiscountTypes::FLAT->value) {
                return PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_FLAT->name;
            }
        }

        return PromotionTypes::ITEM_WISE_BUNDLE_BUY->name;
    }

    public function isCashbackApply(?SaleCashback $saleCashback): bool
    {
        return $saleCashback instanceof SaleCashback;
    }

    public function isLoyaltyPointsUsedAsPayment(Collection $salePayments): bool
    {
        return $salePayments->where('payment_type_id', StaticPaymentTypes::LOYALTY_POINT->value)->isNotEmpty();
    }
}
