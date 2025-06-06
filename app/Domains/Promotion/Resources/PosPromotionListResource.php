<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosPromotionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $promotion = $this->resource;

        [$promotionType , $buyValue, $getValue, $getQuantity, $maxValue] = static::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $preparedData = [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'status' => (int) $promotion->status,
            'promotion_type' => $this->formatAsPerOldFeature($promotionType),
            'promotion_type_key' => $promotionType,
            'timeframe_type' => $promotion->timeframe_type_id ? PromotionTimeframeTypes::getCaseNameByValue(
                $promotion->timeframe_type_id
            ) : null,
            'percentage' => (float) $promotion->percentage,
            'flat_amount' => (float) $promotion->flat_amount,
            'promotion_tiers' => static::getPromotionTiers(
                $promotion->promotionTiers,
                $buyValue,
                $getValue,
                $getQuantity,
                $maxValue
            ),
            'products' => static::getPromotionProductsIds($promotion->regularProducts),
            'buy_products' => static::getPromotionProductsIds($promotion->buyProducts),
            'get_products' => static::getPromotionProductsIds($promotion->getProducts),
            'categories' => static::getPromotionCategoriesIds($promotion->categories),
            'product_collections' => static::getPromotionProductCollectionIds($promotion->productCollections),
            'brands' => static::getPromotionBrandsIds($promotion->brands),
            'member_groups' => static::getPromotionMemberGroupId($promotion->memberGroups),
            'employee_groups' => static::getPromotionEmployeeGroupId($promotion->employeeGroups),
            'tags' => static::getPromotionTags($promotion->tags),
            'start_date' => $promotion->start_date ?? null,
            'end_date' => $promotion->end_date ?? null,
            'start_time' => $promotion->start_time ?? null,
            'end_time' => $promotion->end_time ?? null,
            'month_dates' => static::getPromotionMonthDates($promotion->monthly),
            'week_days' => static::getPromotionWeekDays($promotion->weekly),
            'allow_walk_in_member' => $promotion->allow_walk_in_member,
            'allow_registered_member' => $promotion->allow_registered_member,
            'allow_employee' => $promotion->allow_employee,
            'is_automatic' => $promotion->is_automatic,
            'is_available_in_pos' => $promotion->is_available_in_pos,
            'dream_price_applicable' => $promotion->dream_price_applicable,
            'is_membership_required' => $promotion->is_membership_required,
            'memberships' => static::getPromotionMembershipId($promotion->memberships),
        ];

        if (! $promotion->is_automatic && $this->whenLoaded('saleItemDiscountPromotionPromoCodes')) {
            $promoCodes = $promotion->promotionPromoCodes->pluck('promo_code')->toArray();
            $preparedData['usage_type'] = PromotionUsageTypes::getFormattedArrayForPos($promotion->usage_type);
            $preparedData['promo_codes'] = $promoCodes;

            if ((int) $promotion->usage_type === PromotionUsageTypes::SINGLE_USE->value) {
                $saleItemDiscountPromotionPromoCodes = $promotion->saleItemDiscountPromotionPromoCodes
                    ->whereIn('promo_code', $promoCodes)
                    ->pluck('promo_code')
                    ->toArray();

                $saleDiscountPromotionPromoCodes = $promotion->saleDiscountPromotionPromoCodes
                    ->whereIn('promo_code', $promoCodes)
                    ->pluck('promo_code')
                    ->toArray();

                $preparedData['used_promo_code'] = array_merge(
                    $saleDiscountPromotionPromoCodes,
                    $saleItemDiscountPromotionPromoCodes
                );
            }
        }

        return $preparedData;
    }

    public static function getTypeAndKeyNamesAsPerSelectedPromotion(
        int $promotionApplicableTypeId,
        ?int $cartWidePromotionTypeId,
        ?int $itemWisePromotionTypeId,
        ?int $discountTypeId
    ): array {
        if ($promotionApplicableTypeId === PromotionApplicableTypes::CART_WIDE->value && in_array(
            $cartWidePromotionTypeId,
            [CartWidePromotionTypes::AS_PER_AMOUNT->value, CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value])) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                $type = PromotionTypes::CART_WIDE_AUTOMATIC_PERCENTAGE->name;

                if ($cartWidePromotionTypeId === CartWidePromotionTypes::AS_PER_AMOUNT->value) {
                    $type = PromotionTypes::CART_WIDE_AS_PER_AMOUNT_PERCENTAGE->name;
                }

                if ($cartWidePromotionTypeId === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                    $type = PromotionTypes::CART_WIDE_AS_PER_PAYMENT_TYPE_PERCENTAGE->name;
                }

                return [$type, 'minimum_spend_amount', 'percentage', null, null];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                $type = PromotionTypes::CART_WIDE_AUTOMATIC_FLAT->name;

                if ($cartWidePromotionTypeId === CartWidePromotionTypes::AS_PER_AMOUNT->value) {
                    $type = PromotionTypes::CART_WIDE_AS_PER_AMOUNT_FLAT->name;
                }

                if ($cartWidePromotionTypeId === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                    $type = PromotionTypes::CART_WIDE_AS_PER_PAYMENT_TYPE_FLAT->name;
                }

                return [$type, 'minimum_spend_amount', 'flat_amount', null, null];
            }
        }

        if (
            $itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_TAGS->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value
        ) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_PERCENTAGE->name;

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_PERCENTAGE->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_PERCENTAGE->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_TAGS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_PERCENTAGE->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_PERCENTAGE->name;
                }

                return [$type, 'buy_value', 'get_value', null, null];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_FLAT->name;

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_FLAT->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_FLAT->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_TAGS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_FLAT->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_FLAT->name;
                }

                return [$type, 'buy_value', 'get_value', null, null];
            }
        }

        if (
            $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value
        ) {
            $type = $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value ?
                PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_30_OFF->name :
                PromotionTypes::ITEM_WISE_BUY_2_GET_50_OFF_ON_OTHERS->name;

            return [$type, 'buy_quantity', 'percentage', null, null];
        }

        if (
            $itemWisePromotionTypeId === ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value
        ) {
            $type = PromotionTypes::ITEM_WISE_PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->name;

            return [$type, 'buy_quantity', 'percentage', null, null];
        }

        if (
            $itemWisePromotionTypeId === ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value
        ) {
            $type = PromotionTypes::ITEM_WISE_FLAT_DISCOUNT_FOR_NEXT_ITEM->name;

            return [$type, 'buy_quantity', 'flat_amount', null, null];
        }

        if (
            $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value
        ) {
            $type = $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value ?
                PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->name :
                PromotionTypes::ITEM_WISE_BUY_2_GET_RM_50_OFF_ON_OTHERS->name;

            return [$type, 'buy_quantity', 'flat_amount', null, null];
        }

        if (
            $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_3_GET_1->value ||
            $itemWisePromotionTypeId === ItemWisePromotionTypes::CHEAPEST_FREE->value
        ) {
            $type = $itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_3_GET_1->value ?
                PromotionTypes::ITEM_WISE_BUY_3_GET_1->name :
                PromotionTypes::ITEM_WISE_CHEAPEST_FREE->name;

            return [$type, 'buy_quantity', 'get_quantity', null, null];
        }

        if ($itemWisePromotionTypeId === ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value) {
            return [
                PromotionTypes::ITEM_WISE_GIFT_WITH_PURCHASE->name,
                'minimum_spend_amount',
                'free_quantity',
                null,
                null,
            ];
        }

        if ($itemWisePromotionTypeId === ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value) {
            return [
                PromotionTypes::ITEM_WISE_BUY_2_AND_GET_1_QUANTITY_AT_RM1->name,
                'buy_quantity',
                'amount',
                'get_quantity',
                null,
            ];
        }

        if ($itemWisePromotionTypeId === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_PERCENTAGE->name,
                    'minimum_spend_amount',
                    'percentage',
                    null,
                    null,
                ];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_FLAT->name,
                    'minimum_spend_amount',
                    'flat_amount',
                    null,
                    null,
                ];
            }
        }

        if ($itemWisePromotionTypeId === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_PERCENTAGE->name,
                    'minimum_product_price',
                    'percentage',
                    null,
                    'maximum_product_price',
                ];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT->name,
                    'minimum_product_price',
                    'flat_amount',
                    null,
                    'maximum_product_price',
                ];
            }
        }

        if ($itemWisePromotionTypeId === ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_PERCENTAGE->name,
                    'minimum_spend_amount',
                    'percentage',
                    null,
                    'maximum_spend_amount',
                ];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_FLAT->name,
                    'minimum_spend_amount',
                    'flat_amount',
                    null,
                    'maximum_spend_amount',
                ];
            }
        }

        return [PromotionTypes::ITEM_WISE_BUNDLE_BUY->name, 'buy_quantity', 'amount', null, null];
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionProductsIds(Collection $products): ?array
    {
        if ($products->isEmpty()) {
            return null;
        }

        return $products->map(fn ($product) => $product->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionCategoriesIds(Collection $categories): ?array
    {
        if ($categories->isEmpty()) {
            return null;
        }

        return $categories->map(fn ($category) => $category->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionProductCollectionIds(Collection $productCollections): ?array
    {
        if ($productCollections->isEmpty()) {
            return null;
        }

        return $productCollections->map(fn ($productCollection) => $productCollection->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionBrandsIds(Collection $brands): ?array
    {
        if ($brands->isEmpty()) {
            return null;
        }

        return $brands->map(fn ($brand) => $brand->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionMemberGroupId(Collection $memberGroups): ?array
    {
        if ($memberGroups->isEmpty()) {
            return null;
        }

        return $memberGroups->map(fn ($memberGroup) => $memberGroup->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionEmployeeGroupId(Collection $employeeGroups): ?array
    {
        if ($employeeGroups->isEmpty()) {
            return null;
        }

        return $employeeGroups->map(fn ($employeeGroup) => $employeeGroup->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionMembershipId(Collection $memberships): ?array
    {
        if ($memberships->isEmpty()) {
            return null;
        }

        return $memberships->map(fn ($membership) => $membership->id)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionMonthDates(Collection $monthDates): ?array
    {
        if ($monthDates->isEmpty()) {
            return null;
        }

        return $monthDates->map(fn ($monthDate) => $monthDate->month_date)->toArray();
    }

    /**
     * @return mixed[]|null
     */
    public static function getPromotionWeekDays(Collection $weekDays): ?array
    {
        if ($weekDays->isEmpty()) {
            return null;
        }

        return $weekDays->map(fn ($weekDay) => $weekDay->week_day)->toArray();
    }

    public static function getPromotionTiers(
        Collection $promotionTiers,
        string $buyValue,
        string $getValue,
        ?string $getQuantity,
        ?string $maxValue = null
    ): Collection {
        if ($maxValue) {
            return $promotionTiers->map(fn ($promotionTier): array => [
                $buyValue => (float) $promotionTier->buy_value,
                $maxValue => (float) $promotionTier->max_value,
                $getValue => (float) $promotionTier->get_value,
            ]);
        }

        if ($getQuantity) {
            return $promotionTiers->map(fn ($promotionTier): array => [
                $buyValue => (float) $promotionTier->buy_value,
                $getValue => (float) $promotionTier->get_value,
                $getQuantity => (float) $promotionTier->get_quantity,
            ]);
        }

        return $promotionTiers->map(fn ($promotionTier): array => [
            $buyValue => (float) $promotionTier->buy_value,
            $getValue => (float) $promotionTier->get_value,
        ]);
    }

    public static function getPromotionTags(Collection $tags): Collection
    {
        return $tags->map(fn ($tag): array => [
            'id' => $tag->id,
            'name' => $tag->name,
        ]);
    }

    private function formatAsPerOldFeature(string $promotionType): string
    {
        if ($promotionType === PromotionTypes::CART_WIDE_AS_PER_AMOUNT_PERCENTAGE->name) {
            return PromotionTypes::CART_WIDE_AUTOMATIC_PERCENTAGE->name;
        }

        if ($promotionType === PromotionTypes::CART_WIDE_AS_PER_AMOUNT_FLAT->name) {
            return PromotionTypes::CART_WIDE_AUTOMATIC_FLAT->name;
        }

        return $promotionType;
    }
}
