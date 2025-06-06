<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use App\Domains\Product\Enums\ProductTypes;
use Spatie\LaravelData\Data;

class StoreManagerApiUpdateProductPriceData extends Data
{
    public function __construct(
        public string $type_id,
        public ?float $retail_price,
        public ?float $franchise_price_1,
        public ?float $franchise_price_2,
        public ?float $franchise_price_3,
        public ?float $wholesale_price,
        public ?float $company_or_tender_price,
        public ?float $branch_price,
        public ?float $minimum_price,
        public ?float $original_capital_price,
        public ?float $capital_price,
        public ?float $staff_price,
        public ?float $purchase_cost,
        public ?float $online_price,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'type_id' => ['required', 'integer', 'in:' . ProductTypes::getValues()],
            'retail_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'franchise_price_1' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'franchise_price_2' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'franchise_price_3' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'wholesale_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'company_or_tender_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'branch_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'minimum_price' => [
                'nullable',
                'required_if:type_id,' . ProductTypes::SPECIAL_ORDER->value,
                'required_if:type_id,' . ProductTypes::CUSTOM_ORDER->value,
                'required_if:type_id,' . ProductTypes::POSTAGE_COST->value,
                'numeric',
                'between:0,99999999.99',
            ],
            'original_capital_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'capital_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'staff_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'purchase_cost' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'online_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
        ];
    }
}
