<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class StockMovementsCustomReportData extends Data
{
    public function __construct(
        public ?array $location_ids = null,
        public ?array $category_ids = null,
        public ?array $product_ids = null,
        public ?string $product_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?string $article_number = null,
        public ?array $brand_ids = null,
        public ?array $department_ids = null,
        public ?int $report_type = null,
        public ?int $filter_by = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['required', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['required', 'integer'],
            'product_id' => ['nullable'],
            'product_collection_id' => ['nullable', 'integer'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['required', 'string'],
            'date_range' => ['required'],
            'article_number' => ['nullable', 'string'],
            'brand_ids' => ['nullable', 'array'],
            'department_ids' => ['nullable', 'array'],
            'report_type' => ['required', 'integer'],
            'filter_by' => ['nullable', 'integer'],
        ];
    }
}
