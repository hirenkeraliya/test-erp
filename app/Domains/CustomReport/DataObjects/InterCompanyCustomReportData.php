<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class InterCompanyCustomReportData extends Data
{
    public function __construct(
        public int $transfer_type,
        public bool $display_purchase_cost,
        public ?int $location_id = null,
        public ?int $product_id = null,
        public ?int $product_collection_id = null,
        public ?array $date_range = [],
        public ?int $report_by = null,
        public ?int $filter_by = null,
        public ?string $article_number = null,
        public ?int $external_location_id = null,
        public ?int $external_company_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer'],
            'transfer_type' => ['required', 'integer'],
            'date_range' => ['required', 'array'],
            'filter_by' => ['nullable', 'integer'],
            'report_by' => ['required', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'product_collection_id' => ['nullable', 'integer'],
            'article_number' => ['nullable', 'string'],
            'external_location_id' => ['nullable', 'integer'],
            'external_company_id' => ['nullable', 'integer'],
            'display_purchase_cost' => ['required', 'bool'],
        ];
    }
}
