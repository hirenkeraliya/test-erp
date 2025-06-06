<?php

declare(strict_types=1);

namespace App\Domains\Promotion\DataObjects;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Domains\Promotion\PromotionQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Spatie\LaravelData\Data;

class PromotionData extends Data
{
    public function __construct(
        public string $name,
        public ?array $location_ids,
        public bool $allow_registered_member,
        public bool $allow_employee,
        public bool $allow_walk_in_member,
        public ?array $regular_product_ids,
        public ?array $buy_product_ids,
        public ?array $get_product_ids,
        public ?array $category_ids,
        public ?array $brand_ids,
        public ?string $start_date,
        public ?string $end_date,
        public ?array $week_days,
        public ?array $month_dates,
        public ?string $start_time,
        public ?string $end_time,
        public int $promotion_applicable_type_id,
        public ?int $discount_type_id,
        public ?int $cart_wide_promotion_type_id,
        public ?int $item_wise_promotion_type_id,
        public int $timeframe_type_id,
        public ?float $percentage,
        public ?float $flat_amount,
        public ?array $tiers,
        public ?array $member_group_ids,
        public ?array $tag_ids,
        public ?array $product_collection_ids,
        public ?array $employee_group_ids,
        public bool $dream_price_applicable,
        public bool $is_automatic,
        public ?int $usage_type,
        public bool $is_available_in_ecommerce,
        public ?array $promo_codes,
        public ?array $payment_type_ids,
        public bool $is_membership_required,
        public ?array $membership_ids,
        public bool $is_available_in_pos = true,
        public ?array $sale_channel_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $promotionId = null;
        $promotionQueries = new PromotionQueries();
        $locationIdsRule = 'required';

        if ('admin.promotions.update' === $request->route()?->getName()) {
            $promotionId = $request->route()->parameter('promotionId');
            $locationIdsRule = 'prohibited';
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('promotions', 'name')->ignore($promotionId)
                    ->where($promotionQueries->filterByCompany(session('admin_company_id'))),
            ],
            'location_ids' => [$locationIdsRule, 'array'],
            'location_ids.*' => ['integer'],

            'allow_registered_member' => ['required', 'boolean'],
            'allow_employee' => ['required', 'boolean'],
            'allow_walk_in_member' => ['required', 'boolean'],
            'dream_price_applicable' => ['required', 'boolean'],
            'is_automatic' => ['required', 'boolean'],
            'is_available_in_pos' => ['required', 'boolean'],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'usage_type' => [
                'required_if:is_automatic,false',
                'nullable',
                'integer',
                'in:' . PromotionUsageTypes::getValues(),
            ],
            'promo_codes' => ['required_if:is_automatic,false', 'nullable', 'array'],
            'promo_codes.*' => ['required_if:is_automatic,false', 'nullable', 'string'],
            'regular_product_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::CHEAPEST_FREE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUNDLE_BUY->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'nullable',
                'array',
            ],
            'regular_product_ids.*' => ['integer'],

            'buy_product_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_3_GET_1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'nullable',
                'array',
            ],
            'buy_product_ids.*' => ['integer'],

            'get_product_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_3_GET_1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'nullable',
                'array',
            ],
            'get_product_ids.*' => ['integer'],

            'category_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                'nullable',
                'array',
            ],
            'category_ids.*' => ['integer'],

            'brand_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::LIMITED_TO_BRANDS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'nullable',
                'array',
            ],
            'brand_ids.*' => ['integer'],

            'tag_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::LIMITED_TO_TAGS->value,
                'nullable',
                'array',
            ],
            'tag_ids.*' => ['integer'],

            'product_collection_ids' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value,
                'nullable',
                'array',
            ],
            'product_collection_ids.*' => ['required', 'integer'],

            'member_group_ids' => ['nullable', 'array'],
            'member_group_ids.*' => ['integer'],

            'employee_group_ids' => ['nullable', 'array'],
            'employee_group_ids.*' => ['integer'],

            'start_date' => [
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value,
                'nullable',
            ],
            'end_date' => [
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMITED_BY_DATES->value,
                'after:start_date',
                'nullable',
            ],
            'week_days' => [
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
                'nullable',
                'array',
            ],
            'week_days.*' => ['integer'],

            'month_dates' => [
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
                'nullable',
                'array',
            ],
            'month_dates.*' => ['integer'],

            'start_time' => [
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value,
                'nullable',
            ],
            'end_time' => [
                'required_if:timeframe_type_id,' . PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value,
                'nullable',
                'after:start_time',
            ],
            'promotion_applicable_type_id' => [
                'required',
                'integer',
                'in:' . PromotionApplicableTypes::getValues(),
            ],
            'discount_type_id' => ['nullable', 'integer', 'in:' . DiscountTypes::getValues()],
            'cart_wide_promotion_type_id' => ['nullable', 'integer', 'in:' . CartWidePromotionTypes::getValues()],
            'item_wise_promotion_type_id' => ['nullable', 'integer', 'in:' . ItemWisePromotionTypes::getValues()],
            'timeframe_type_id' => ['required', 'integer', 'in:' . PromotionTimeframeTypes::getValues()],
            'percentage' => [
                self::validateRequiredField(
                    $request,
                    ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                    DiscountTypes::PERCENTAGE->value
                ),
                self::validateRequiredField(
                    $request,
                    ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                    DiscountTypes::PERCENTAGE->value
                ),
                'nullable',
                'numeric',
                'max:100',
                'min:0.01',
            ],
            'flat_amount' => [
                self::validateRequiredField(
                    $request,
                    ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
                    DiscountTypes::FLAT->value
                ),
                self::validateRequiredField(
                    $request,
                    ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
                    DiscountTypes::FLAT->value
                ),
                'nullable',
                'numeric',
                'min:1',
            ],
            'tiers' => [
                'required_if:promotion_applicable_type_id,' . PromotionApplicableTypes::CART_WIDE->value,
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUNDLE_BUY->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_3_GET_1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::CHEAPEST_FREE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'nullable',
                'array',
            ],
            'tiers.*.buy_value' => [
                'required_if:promotion_applicable_type_id,' . PromotionApplicableTypes::CART_WIDE->value,
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUNDLE_BUY->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_3_GET_1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::CHEAPEST_FREE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'nullable',
                'numeric',
                self::validateMinimumSpendAmount($request),
            ],
            'tiers.*.get_quantity' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'nullable',
                'numeric',
                self::validateGetQuantity(),
            ],
            'tiers.*.max_value' => [
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'nullable',
                'numeric',
                'min:0.01',
                'max:99999999.99',
                'gt:tiers.*.buy_value',
            ],
            'tiers.*.get_value' => [
                'required_if:promotion_applicable_type_id,' . PromotionApplicableTypes::CART_WIDE->value,
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_AMOUNT->value,
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUNDLE_BUY->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_3_GET_1->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::CHEAPEST_FREE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
                'required_if:item_wise_promotion_type_id,' . ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
                'nullable',
                'numeric',
                self::validatePercentage($request),
            ],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
            'payment_type_ids' => [
                'required_if:cart_wide_promotion_type_id,' . CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
                'nullable',
                'array',
            ],
            'payment_type_ids.*' => ['required', 'integer', 'exists:payment_types,id'],
            'is_membership_required' => ['required', 'boolean'],
            'membership_ids' => ['required_if:is_membership_required,true', 'nullable', 'array'],
            'membership_ids.*' => ['integer'],
        ];
    }

    private static function validateRequiredField(
        Request $request,
        int $itemWisePromotionType,
        int $discountType
    ): RequiredIf {
        return Rule::requiredIf(
            fn (): bool => ($request->input('item_wise_promotion_type_id') === $itemWisePromotionType)
                && $request->input('discount_type_id') === $discountType
        );
    }

    /**
     * @return array<string, mixed[]>
     */
    private static function validateMinimumSpendAmount(Request $request): array
    {
        if (in_array(
            $request->input('cart_wide_promotion_type_id'),
            [CartWidePromotionTypes::AS_PER_AMOUNT->value, CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value])) {
            return [
                'tiers.*.buy_value' => ['min:0.01', 'max:99999999.99'],
            ];
        }

        if ($request->input('item_wise_promotion_type_id') === ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value) {
            return [
                'tiers.*.buy_value' => ['min:0.01', 'max:99999999.99'],
            ];
        }

        if ($request->input('item_wise_promotion_type_id') === ItemWisePromotionTypes::CHEAPEST_FREE->value) {
            return [
                'tiers.*.buy_value' => ['min:2'],
            ];
        }

        return [
            'tiers.*.buy_value' => ['min:1'],
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    private static function validatePercentage(Request $request): array
    {
        if (
            in_array(
                $request->input('cart_wide_promotion_type_id'),
                [CartWidePromotionTypes::AS_PER_AMOUNT->value, CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value])
            && $request->input('discount_type_id') === DiscountTypes::PERCENTAGE->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value
            && $request->input('discount_type_id') === DiscountTypes::PERCENTAGE->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value
            && $request->input('discount_type_id') === DiscountTypes::PERCENTAGE->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value
            && $request->input('discount_type_id') === DiscountTypes::PERCENTAGE->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id') === ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0', 'max:100'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01'],
            ];
        }

        if (
            $request->input('item_wise_promotion_type_id')
            === ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01'],
            ];
        }

        return [
            'tiers.*.get_value' => ['min:1'],
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    private static function validateGetQuantity(): array
    {
        return [
            'tiers.*.get_quantity' => ['min:0.01', 'max:99999999.99'],
        ];
    }
}
