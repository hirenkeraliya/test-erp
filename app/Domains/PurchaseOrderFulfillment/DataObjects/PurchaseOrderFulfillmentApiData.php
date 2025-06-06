<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\DataObjects;

use Spatie\LaravelData\Data;

class PurchaseOrderFulfillmentApiData extends Data
{
    public function __construct(
        public int $company_id,
        public int $purchase_order_id,
        public int $external_purchase_order_fulfillment_id,
        public string $happened_at,
        public ?string $notes,
        public string $delivery_order_number,
        public int $status,
        public array $items,
        public ?string $external_username = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'happened_at' => ['required', 'date', 'max:255', 'date_format:Y-m-d H:i:s'],
            'company_id' => ['required', 'integer'],
            'purchase_order_id' => ['required', 'integer'],
            'external_purchase_order_fulfillment_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
            'delivery_order_number' => ['required', 'string'],
            'status' => ['required', 'integer'],
            'items' => ['required', 'array'],
            'items.*.external_purchase_order_fulfillment_item_id' => ['required', 'integer'],
            'items.*.purchase_order_item_id' => ['required', 'integer'],
            'items.*.upc' => ['required', 'string'],
            'items.*.transfer_quantity' => ['required', 'numeric', 'min:0.00'],
            'items.*.received_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'items.*.remarks' => ['nullable', 'string'],
            'transfer_items.*.package_type' => ['nullable', 'string'],
            'transfer_items.*.package_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'transfer_items.*.package_total_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'transfer_items.*.batch_details' => ['nullable', 'array'],
            'transfer_items.*.batch_details.*.batch_number' => ['required', 'string'],
            'transfer_items.*.batch_details.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
