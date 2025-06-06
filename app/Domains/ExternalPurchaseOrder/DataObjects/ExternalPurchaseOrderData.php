<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrder\DataObjects;

use Spatie\LaravelData\Data;

class ExternalPurchaseOrderData extends Data
{
    public function __construct(
        public array $transfer_items,
        public ?string $notes = null,
        public ?float $fob = 0,
        public ?float $freight_charges = 0,
        public ?float $insurance_charges = 0,
        public ?float $duty = 0,
        public ?float $sst = 0,
        public ?float $handling_charges = 0,
        public ?float $other_charges = 0,
    ) {
    }

    public static function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'fob' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'freight_charges' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'insurance_charges' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'duty' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'sst' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'handling_charges' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'other_charges' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'transfer_items' => ['required', 'array'],
            'transfer_items.*.purchase_plan_item_id' => ['required', 'integer'],
            'transfer_items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'transfer_items.*.received_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'transfer_items.*.cost_price' => ['numeric', 'min:0.00'],
            'transfer_items.*.remarks' => ['nullable', 'string'],
        ];
    }
}
