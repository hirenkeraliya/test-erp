<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderItem\DataObjects;

use Spatie\LaravelData\Data;

class WarehouseManagerApiPurchaseOrderItemData extends Data
{
    public function __construct(
        public int $id,
        public ?int $warehouse_id,
        public ?int $location_id,
        public int $per_page,
        public int $page,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'warehouse_id' => ['required_without:location_id', 'integer'],
            'location_id' => ['required_without:warehouse_id', 'integer'],
            'per_page' => ['required', 'integer'],
            'page' => ['required', 'integer'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
