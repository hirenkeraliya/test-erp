<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class StockSummaryByModuleReportData extends Data
{
    public function __construct(
        public ?int $report_by = null,
        public ?int $report_type = null,
        public ?array $location_ids = null,
        public ?array $date_range = [],
        public ?array $article_number = null,
        public ?array $brand_ids = null,
        public ?array $department_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'report_by' => ['required', 'integer'],
            'report_type' => ['required', 'integer'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['required', 'string'],
            'date_range' => ['required', 'array'],
            'article_number' => ['nullable', 'array'],
            'article_number.*' => ['required', 'string'],
            'brand_ids' => ['nullable', 'array'],
            'brand_ids.*' => ['required', 'string'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['required', 'string'],
        ];
    }
}
