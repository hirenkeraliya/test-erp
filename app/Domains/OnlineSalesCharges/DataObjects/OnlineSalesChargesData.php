<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges\DataObjects;

use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use Spatie\LaravelData\Data;

class OnlineSalesChargesData extends Data
{
    public function __construct(
        public string $name,
        public int $shipping_zone_id,
        public int $shipping_charge_type_id,
        public ?float $minimum_value = null,
        public ?float $maximum_value = null,
        public ?float $amount = null,
        public bool $is_available_in_ecommerce = false,
        public ?array $sale_channel_ids = null,
        public array $online_sales_charge_tiers = [],
    ) {
    }

    public static function rules(): array
    {
        $chargeTypeValues = array_map(fn ($case) => $case->value, ShippingChargeTypes::cases());
        $nonWeightTypes = array_diff($chargeTypeValues, [ShippingChargeTypes::WEIGHT->value]);

        return [
            'shipping_charge_type_id' => ['required', 'integer', 'in:' . implode(',', $chargeTypeValues)],
            'name' => ['required', 'string', 'max:255'],
            'minimum_value' => [
                'required_if:shipping_charge_type_id,' . implode(',', $nonWeightTypes),
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'maximum_value' => [
                'required_if:shipping_charge_type_id,' . implode(',', $nonWeightTypes),
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'amount' => [
                'required_if:shipping_charge_type_id,' . implode(',', $nonWeightTypes),
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'online_sales_charge_tiers' => [
                'required_if:shipping_charge_type_id,' . ShippingChargeTypes::WEIGHT->value,
                'nullable',
                'array',
            ],
            'online_sales_charge_tiers.*.min_weight' => [
                'required_with:online_sales_charge_tiers',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'online_sales_charge_tiers.*.max_weight' => [
                'required_with:online_sales_charge_tiers',
                'numeric',
                'min:0',
                'max:99999999.99',
                'gt:online_sales_charge_tiers.*.min_weight',
            ],
            'online_sales_charge_tiers.*.amount' => [
                'required_with:online_sales_charge_tiers',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
        ];
    }
}
