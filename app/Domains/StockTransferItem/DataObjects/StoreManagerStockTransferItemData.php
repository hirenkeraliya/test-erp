<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerStockTransferItemData extends Data
{
    public function __construct(
        public int $id,
        public int $page,
        public int $per_page,
        public ?int $store_id,
        public ?int $location_id,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'page' => ['required', 'integer'],
            'per_page' => ['required', 'integer'],
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
