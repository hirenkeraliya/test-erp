<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\DataObjects;

use Spatie\LaravelData\Data;

class PurchaseOrderFulfillmentUpdateApiData extends Data
{
    public function __construct(
        public int $company_id,
        public int $purchase_order_id,
        public int $purchase_order_fulfillment_id,
        public int $status,
        public array $items,
        public ?string $external_username = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'company_id' => ['required', 'integer'],
            'purchase_order_id' => ['required', 'integer'],
            'purchase_order_fulfillment_id' => ['required', 'integer'],
            'status' => ['required', 'integer'],
            'items' => ['required', 'array'],
            'items.*.external_purchase_order_fulfillment_item_id' => ['required', 'integer'],
            'items.*.purchase_order_fulfillment_item_id' => ['nullable', 'integer'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer'],
            'items.*.external_purchase_order_item_id' => ['required', 'integer'],
            'items.*.unit_of_measure_derivative' => ['nullable', 'string'],
            'items.*.upc' => ['required', 'string'],
            'items.*.transfer_quantity' => ['required', 'numeric', 'min:0.00'],
            'items.*.received_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'items.*.package_type' => ['nullable', 'string'],
            'items.*.package_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'items.*.package_total_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'items.*.is_extra_item' => ['nullable', 'boolean'],
            'items.*.discrepancy_type' => ['nullable', 'numeric'],
            'items.*.remarks' => ['nullable', 'string'],
            'items.*.discrepancy_proof' => ['nullable', 'string'],
            'items.*.batch_details' => ['nullable', 'array'],
        ];
    }
}
