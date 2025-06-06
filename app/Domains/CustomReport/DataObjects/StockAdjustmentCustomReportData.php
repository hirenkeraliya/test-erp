<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class StockAdjustmentCustomReportData extends Data
{
    public function __construct(
        public ?array $location_ids = null,
        public ?int $stock_adjustment_type = null,
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?int $report_type = null,
        public ?int $filter_by = null,
        public ?string $article_number = null,
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
            'stock_adjustment_type' => ['nullable', 'integer'],
            'date_range' => ['required', 'array'],
            'filter_by' => ['nullable', 'integer'],
            'report_type' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'article_number' => ['nullable', 'string'],
        ];
    }
}
