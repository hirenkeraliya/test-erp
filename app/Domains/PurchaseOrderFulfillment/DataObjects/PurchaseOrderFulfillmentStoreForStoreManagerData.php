<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\DataObjects;

use Spatie\LaravelData\Data;

class PurchaseOrderFulfillmentStoreForStoreManagerData extends Data
{
    public function __construct(
        public ?int $store_id,
        public ?int $location_id,
        public int $purchase_order_id,
        public string $happened_at,
        public ?string $notes,
        public array $transfer_items,
    ) {
    }

    public static function rules(): array
    {
        return [
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'purchase_order_id' => ['required', 'integer'],
            'happened_at' => ['required', 'date', 'max:255', 'date_format:Y-m-d H:i:s'],
            'notes' => ['nullable', 'string', 'max:255'],
            'transfer_items' => ['required', 'array'],
            'transfer_items.*.purchase_order_item_id' => ['required', 'integer'],
            'transfer_items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'transfer_items.*.transfer_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'transfer_items.*.remarks' => ['nullable', 'string'],
            'transfer_items.*.package_type_id' => ['nullable', 'integer'],
            'transfer_items.*.package_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'transfer_items.*.package_total_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'transfer_items.*.batch_details' => ['nullable', 'array'],
            'transfer_items.*.batch_details.*.batch_number' => ['required', 'string'],
            'transfer_items.*.batch_details.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
