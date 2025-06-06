<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\DataObjects;

use App\Domains\PurchaseOrder\Enums\Statuses;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class StoreManagerApiPurchaseOrderFulfillmentData extends Data
{
    public function __construct(
        public int $page,
        public int $per_page,
        public ?int $store_id,
        public ?int $location_id,
        public int $purchase_order_id,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?int $status = null,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'page' => ['required', 'integer'],
            'per_page' => ['required', 'integer'],
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'purchase_order_id' => ['required', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,happened_at,delivery_order_number'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'integer', new Enum(Statuses::class)],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
