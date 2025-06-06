<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use Spatie\LaravelData\Data;

class WarehouseManagerApiProductData extends Data
{
    public function __construct(
        public int $page,
        public ?int $warehouse_id,
        public ?int $location_id,
        public string $stock_product,
        public ?string $sort_direction = null,
        public ?int $per_page = null,
        public ?string $sort_by = null,
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
            'warehouse_id' => ['required_without:location_id', 'integer'],
            'location_id' => ['required_without:warehouse_id', 'integer'],
            'stock_product' => ['required', 'in:all,no_stock,in_stock,low_stock'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name,article_number,retail_price,upc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
