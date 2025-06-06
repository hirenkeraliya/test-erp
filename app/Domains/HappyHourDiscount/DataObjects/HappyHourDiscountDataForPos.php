<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\DataObjects;

use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Models\HappyHourDiscountTransaction;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class HappyHourDiscountDataForPos extends Data
{
    public function __construct(
        public string $offline_id,
        public int $product_type_id,
        public string $name,
        public string $new_price,
        public string $start_date,
        public string $end_date,
        public string $happened_at,
        public ?int $store_manager_id,
        public ?string $store_manager_passcode,
        public ?int $director_id,
        public ?string $director_passcode,
        public ?string $store_manager_authorization_code = null,
        public ?array $brand_ids = null,
        public ?array $category_ids = null,
        public ?array $style_ids = null,
        public ?array $department_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'offline_id' => ['required', 'string', Rule::unique(HappyHourDiscountTransaction::class, 'offline_id')],
            'product_type_id' => ['required', 'integer', new Enum(ProductTypes::class)],
            'name' => ['required', 'string'],
            'new_price' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date_format:Y-m-d H:i:s', 'after_or_equal:happened_at'],
            'end_date' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
            'director_id' => ['sometimes', 'nullable', 'integer'],
            'director_passcode' => ['sometimes', 'nullable', 'string'],
            'brand_ids' => ['required_if:product_type_id,' . ProductTypes::BRAND->value, 'nullable', 'array'],
            'brand_ids.*' => ['required', 'integer'],
            'style_ids' => ['required_if:product_type_id,' . ProductTypes::STYLE->value, 'nullable', 'array'],
            'style_ids.*' => ['required', 'integer'],
            'department_ids' => [
                'required_if:product_type_id,' . ProductTypes::DEPARTMENTS->value,
                'nullable',
                'array',
            ],
            'department_ids.*' => ['required', 'integer'],
            'category_ids' => ['required_if:product_type_id,' . ProductTypes::CATEGORY->value, 'nullable', 'array'],
            'category_ids.*' => ['required', 'integer'],
        ];
    }
}
