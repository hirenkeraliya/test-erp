<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\DataObjects;

use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class HappyHourDiscountData extends Data
{
    public function __construct(
        public int $product_type_id,
        public string $name,
        public float $new_price,
        public string $start_date,
        public string $end_date,
        public ?int $location_id,
        public ?array $brand_ids = null,
        public ?array $category_ids = null,
        public ?array $style_ids = null,
        public ?array $department_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $locationQueries = resolve(LocationQueries::class);

        return [
            'product_type_id' => ['required', 'integer', 'in:' . ProductTypes::getValues()],
            'name' => ['required', 'string', 'max:255'],
            'new_price' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date' => ['required', 'after:start_date', 'date_format:Y-m-d H:i:s'],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where(
                        $locationQueries->filterByCompanyAndTypeId(session(
                            'admin_company_id'
                        ), LocationTypes::STORE->value)
                    ),
            ],
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
