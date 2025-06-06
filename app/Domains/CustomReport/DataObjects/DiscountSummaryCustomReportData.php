<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class DiscountSummaryCustomReportData extends Data
{
    public function __construct(
        public array $date_range = [],
        public ?int $filter_by = null,
        public ?array $location_ids = [],
        public ?array $brand_ids = null,
        public ?array $department_ids = null,
        public ?array $tag_ids = null,
        public ?array $style_ids = null,
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?int $article_number = null,
        public ?int $report_type = null,
        public ?int $sale_discount_type = null,
        public ?array $attribute_values = null,
        public ?int $attribute_type = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'brand_ids' => ['nullable', 'array'],
            'department_ids' => ['nullable', 'array'],
            'tag_ids' => ['nullable', 'array'],
            'style_ids' => ['nullable', 'array'],
            'date_range' => ['required', 'array'],
            'filter_by' => ['nullable', 'integer'],
            'report_type' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'article_number' => ['nullable', 'integer'],
            'sale_discount_type' => ['required', 'integer'],
            'attribute_values' => ['nullable', 'array'],
            'attribute_type' => ['nullable', 'integer'],
        ];
    }
}
