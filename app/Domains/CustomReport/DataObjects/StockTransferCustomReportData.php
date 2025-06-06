<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use App\Domains\StockTransfer\Enums\StockTransferCustomReportDateTypes;
use Spatie\LaravelData\Data;

class StockTransferCustomReportData extends Data
{
    public function __construct(
        public int $transfer_type,
        public string $display_total_price,
        public int $date_type,
        public int $display_date_type,
        public ?array $location_ids = [],
        public ?int $additional_location_id = null,
        public ?array $status_type = [],
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?int $report_by = null,
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
            'additional_location_id' => ['nullable', 'string'],
            'transfer_type' => ['required', 'integer'],
            'status_type' => ['nullable', 'array'],
            'date_type' => ['required', 'integer', 'in:' . StockTransferCustomReportDateTypes::getValues()],
            'display_date_type' => ['required', 'integer', 'in:' . StockTransferCustomReportDateTypes::getValues()],
            'date_range' => ['required', 'array'],
            'filter_by' => ['nullable', 'integer'],
            'report_by' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'article_number' => ['nullable', 'string'],
            'display_total_price' => ['required', 'string'],
        ];
    }
}
