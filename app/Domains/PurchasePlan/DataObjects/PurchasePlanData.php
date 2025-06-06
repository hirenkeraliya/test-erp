<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\DataObjects;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PurchasePlanData extends Data
{
    public function __construct(
        public int $vendor_id,
        public int $location_id,
        public ?float $total_amount,
        public ?string $reference_number,
        public ?string $remarks,
        public array $transfer_items,
    ) {
    }

    public static function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'location_id' => ['required', 'integer'],
            'total_amount' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'transfer_items' => ['required', 'nullable', 'array'],
            'transfer_items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'transfer_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'transfer_items.*.unit_of_measure_derivative_id' => ['nullable', 'integer'],
            'transfer_items.*.remarks' => ['nullable', 'string'],
            'transfer_items.*.is_product_purchase_cost' => ['required', 'boolean'],
            'transfer_items.*.purchase_cost' => [
                'required_if:transfer_items.*.is_product_purchase_cost,false',
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
        ];
    }
}
