<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Resources;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionTypes;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminPromotionDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Promotion $promotion */
        $promotion = $this;

        /** @var Collection $memberGroups */
        $memberGroups = $promotion->memberGroups;

        /** @var Collection $employeeGroups */
        $employeeGroups = $promotion->employeeGroups;

        /** @var Collection $memberships */
        $memberships = $promotion->memberships;

        /** @var Collection $promoCodes */
        $promoCodes = $promotion->promotionPromoCodes;

        [$promotionType , $buyValue, $getValue, $getQuantity, $maxValue] = static::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $startDate = '';

        if ($promotion->start_date) {
            /** @var Carbon $startDateFormat */
            $startDateFormat = Carbon::createFromFormat('Y-m-d', $promotion->start_date);
            $startDate = $startDateFormat->format('d-m-Y');
        }

        $endDate = '';

        if ($promotion->end_date) {
            /** @var Carbon $endDateFormat */
            $endDateFormat = Carbon::createFromFormat('Y-m-d', $promotion->end_date);
            $endDate = $endDateFormat->format('d-m-Y');
        }

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'locations' => $promotion->locations,
            'promotion_type' => CommonFunctions::stringTitleLowerCase($promotionType),
            'promotion_applicable_type' => $promotion->promotion_applicable_type_id ? PromotionApplicableTypes::getFormattedCaseName(
                $promotion->promotion_applicable_type_id
            ) : 'N/A',
            'type' => $promotion->discount_type_id ? DiscountTypes::getFormattedCaseName(
                $promotion->discount_type_id
            ) : 'N/A',
            'item_wise_promotion_type' => $promotion->item_wise_promotion_type_id ? ItemWisePromotionTypes::getFormattedCaseName(
                $promotion->item_wise_promotion_type_id
            ) : '',
            'item_wise_promotion_type_id' => $promotion->item_wise_promotion_type_id,
            'cart_wide_promotion_type' => $promotion->cart_wide_promotion_type_id ? CartWidePromotionTypes::getFormattedCaseName(
                $promotion->cart_wide_promotion_type_id
            ) : '',
            'timeframe_type' => $this->timeFrameTypesWithDetails($promotion->timeframe_type_id),
            'promotion_tiers' => static::getPromotionTiers(
                $promotion->promotionTiers,
                $buyValue,
                $getValue,
                $getQuantity,
                $maxValue
            ),
            'percentage' => $promotion->percentage,
            'flat_amount' => $promotion->flat_amount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $promotion->status,
            'allow_walk_in_member' => $promotion->allow_walk_in_member,
            'allow_registered_member' => $promotion->allow_registered_member,
            'allow_employee' => $promotion->allow_employee,
            'is_membership_required' => $promotion->is_membership_required,

            'member_groups' => $memberGroups->map(fn ($memberGroup): array => [
                'name' => $memberGroup->name,
            ]),

            'employee_groups' => $employeeGroups->map(fn ($employeeGroup): array => [
                'name' => $employeeGroup->name,
            ]),

            'memberships' => $memberships->map(fn ($membership): array => [
                'name' => $membership->name,
            ]),

            'promo_codes' => $promoCodes->map(fn ($promoCode): array => [
                'name' => $promoCode->promo_code,
            ]),
        ];
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
                $type = PromotionTypes::CART_WIDE_AS_PER_AMOUNT_PERCENTAGE->name;

                if ($cartWidePromotionTypeId === CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value) {
                    $type = PromotionTypes::CART_WIDE_AS_PER_PAYMENT_TYPE_PERCENTAGE->name;
                }

                return [$type, 'minimum_spend_amount', 'percentage', null, null];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                $type = PromotionTypes::CART_WIDE_AS_PER_AMOUNT_FLAT->name;

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
            ItemWisePromotionTypes::LIMITED_TO_TAGS->value === $itemWisePromotionTypeId
             ||
            ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value === $itemWisePromotionTypeId
        ) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCTS_PERCENTAGE->name;

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_PERCENTAGE->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_BRANDS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_BRANDS_PERCENTAGE->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_PERCENTAGE->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_TAGS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_PERCENTAGE->name;
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

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_PRODUCT_COLLECTION_FLAT->name;
                }

                if ($itemWisePromotionTypeId === ItemWisePromotionTypes::LIMITED_TO_TAGS->value) {
                    $type = PromotionTypes::ITEM_WISE_LIMITED_TO_TAGS_FLAT->name;
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
                    'minimum_spend_amount',
                    'percentage',
                    null,
                    'maximum_spend_amount',
                ];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT->name,
                    'minimum_spend_amount',
                    'flat_amount',
                    null,
                    'maximum_spend_amount',
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

        if ($itemWisePromotionTypeId === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value) {
            if ($discountTypeId === DiscountTypes::PERCENTAGE->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_PERCENTAGE->name,
                    'minimum_spend_amount',
                    'percentage',
                    null,
                    null,
                ];
            }

            if ($discountTypeId === DiscountTypes::FLAT->value) {
                return [
                    PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT->name,
                    'minimum_spend_amount',
                    'flat_amount',
                    null,
                    null,
                ];
            }
        }

        return [PromotionTypes::ITEM_WISE_BUNDLE_BUY->name, 'buy_quantity', 'amount', null, null];
    }

    public static function getPromotionTiers(
        Collection $promotionTiers,
        string $buyValue,
        string $getValue,
        ?string $getQuantity,
        ?string $maxValue,
    ): Collection {
        if ($getQuantity) {
            return $promotionTiers->map(fn ($promotionTier): array => [
                $buyValue => (float) $promotionTier->buy_value,
                $getValue => (float) $promotionTier->get_value,
                $getQuantity => (float) $promotionTier->get_quantity,
            ]);
        }

        if ($maxValue) {
            return $promotionTiers->map(fn ($promotionTier): array => [
                $buyValue => (float) $promotionTier->buy_value,
                $getValue => (float) $promotionTier->get_value,
                $maxValue => (float) $promotionTier->max_value,
            ]);
        }

        return $promotionTiers->map(fn ($promotionTier): array => [
            $buyValue => (float) $promotionTier->buy_value,
            $getValue => (float) $promotionTier->get_value,
        ]);
    }

    private function timeFrameTypesWithDetails(int $timeFrameTypeId): string
    {
        /** @var Promotion $promotion */
        $promotion = $this;

        $startDate = '';

        if ($promotion->start_date) {
            /** @var Carbon $startDateFormat */
            $startDateFormat = Carbon::createFromFormat('Y-m-d', $promotion->start_date);
            $startDate = $startDateFormat->format('d-m-Y');
        }

        $endDate = '';

        if ($promotion->end_date) {
            /** @var Carbon $endDateFormat */
            $endDateFormat = Carbon::createFromFormat('Y-m-d', $promotion->end_date);
            $endDate = $endDateFormat->format('d-m-Y');
        }

        if ($timeFrameTypeId === PromotionTimeframeTypes::LIMITED_BY_DATES->value) {
            return PromotionTimeframeTypes::getFormattedCaseName(
                $timeFrameTypeId
            ) . ' (' . $startDate . ' to ' . $endDate . ')';
        }

        if ($timeFrameTypeId === PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value) {
            $days = [
                'Monday' => 1,
                'Tuesday' => 2,
                'Wednesday' => 3,
                'Thursday' => 4,
                'Friday' => 5,
                'Saturday' => 6,
                'Sunday' => 7,
            ];

            return PromotionTimeframeTypes::getFormattedCaseName($timeFrameTypeId) . ' (' . implode(
                ', ',
                array_keys(array_intersect($days, $promotion->weekly->pluck('week_day')->toArray()))
            ) . ')';
        }

        if ($timeFrameTypeId === PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value) {
            return PromotionTimeframeTypes::getFormattedCaseName($timeFrameTypeId) . ' (' . implode(
                ', ',
                $promotion->monthly->pluck('month_date')->toArray()
            ) . ')';
        }

        if ($timeFrameTypeId === PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value) {
            $startTime = '';
            if ($promotion->start_time) {
                $startTimeFormat = Carbon::createFromFormat('H:i:s', $promotion->start_time);
                $startTime = $startTimeFormat ? $startTimeFormat->format('h:i:s A') : '';
            }

            $endTime = '';
            if ($promotion->end_time) {
                $endTimeFormat = Carbon::createFromFormat('H:i:s', $promotion->end_time);
                $endTime = $endTimeFormat ? $endTimeFormat->format('h:i:s A') : '';
            }

            return PromotionTimeframeTypes::getFormattedCaseName(
                $timeFrameTypeId
            ) . ' (' . $startDate . ': ' . $startTime . ' to ' . $endTime . ')';
        }

        if ($timeFrameTypeId === PromotionTimeframeTypes::NO_LIMIT->value) {
            return 'No Limit';
        }

        return 'N/A';
    }
}
