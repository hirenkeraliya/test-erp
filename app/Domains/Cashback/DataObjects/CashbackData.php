<?php

declare(strict_types=1);

namespace App\Domains\Cashback\DataObjects;

use App\Domains\Cashback\Enums\ConditionTypes;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class CashbackData extends Data
{
    public function __construct(
        public string $name,
        public array $location_ids,
        public int $exclude_by_type,
        public int $discount_type_id,
        public float $discount_value,
        public ?array $product_ids,
        public ?array $category_ids,
        public float $minimum_spend_amount,
        public string $start_date,
        public string $end_date,
        public ?array $tiers,
    ) {
    }

    public static function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'exclude_by_type' => ['required', 'integer', 'in:' . ExcludeByTypes::getValues()],
            'minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'after:start_date', 'date_format:Y-m-d'],
            'category_ids' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::CATEGORIES->value,
                'nullable',
                'array',
            ],
            'category_ids.*' => ['integer'],
            'product_ids' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::PRODUCTS->value,
                'nullable',
                'array',
            ],
            'product_ids.*' => ['integer'],
            'tiers' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::ORIGINAL_ITEM_PRICE->value . ', exclude_by_type, ' . ExcludeByTypes::DISCOUNT_ITEM_PRICE->value,
                'array',
                'nullable',
            ],
            'tiers.*.condition_operator_type_id' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::ORIGINAL_ITEM_PRICE->value . ', exclude_by_type, ' . ExcludeByTypes::DISCOUNT_ITEM_PRICE->value,
                'required',
                'in:' . ConditionTypes::getValues(),
            ],
            'tiers.*.amount' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::ORIGINAL_ITEM_PRICE->value . ', exclude_by_type, ' . ExcludeByTypes::DISCOUNT_ITEM_PRICE->value,
                'required',
                'numeric',
                'between:1,99999999.99',
            ],
            'discount_type_id' => ['required', 'integer', 'in:' . DiscountTypes::getValues()],
            'discount_value' => array_merge(['required', 'numeric'], self::validateDiscountValue($request)),
        ];
    }

    private static function validateDiscountValue(Request $request): array
    {
        if ($request->input('discount_type_id') === DiscountTypes::FLAT->value) {
            return ['min:0.01'];
        }

        return ['min:0.01', 'max:100'];
    }
}
