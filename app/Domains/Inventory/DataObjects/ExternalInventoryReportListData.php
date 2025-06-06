<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DataObjects;

use Spatie\LaravelData\Data;

class ExternalInventoryReportListData extends Data
{
    public function __construct(
        public ?string $search_text = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?int $per_page = null,
        public ?int $page = null,
        public ?int $product_id = null,
        public ?int $category_id = null,
        public ?int $brand_id = null,
        public ?int $color_id = null,
        public ?int $size_id = null,
        public ?array $location_ids = [],
        public ?array $article_numbers = [],
        public ?array $department_ids = [],
        public ?array $tag_ids = [],
        public ?int $stock_type = null,
        public ?array $style_ids = [],
        public ?array $region_ids = [],
        public ?array $attributes = [],
        public ?int $selling_type = null,
        public ?string $status = null,
        public ?int $external_company_main_id = null,
        public array $export_columns = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'search_text' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
            'sort_by' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'string'],
            'product_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'color_id' => ['nullable', 'integer'],
            'size_id' => ['nullable', 'integer'],
            'location_ids' => ['nullable', 'array'],
            'article_numbers' => ['nullable', 'array'],
            'department_ids' => ['nullable', 'array'],
            'tag_ids' => ['nullable', 'array'],
            'stock_type' => ['nullable', 'integer'],
            'style_ids' => ['nullable', 'array'],
            'selling_type' => ['nullable', 'integer'],
            'region_ids' => ['nullable', 'array'],
            'attributes' => ['nullable', 'array'],
            'status' => ['nullable', 'string'],
            'external_company_main_id' => ['nullable', 'integer'],
            'export_columns' => ['nullable', 'array'],
        ];
    }
}
