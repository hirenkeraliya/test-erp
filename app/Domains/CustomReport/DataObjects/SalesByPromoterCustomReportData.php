<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class SalesByPromoterCustomReportData extends Data
{
    public function __construct(
        public ?array $location_ids = null,
        public ?array $date_range = [],
        public ?int $filter_by = null,
        public ?array $brand_ids = null,
        public ?array $department_ids = null,
        public ?array $category_ids = null,
        public ?array $promoter_ids = null,
        public ?int $report_type = null,
        public ?array $group_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'date_range' => ['required', 'array'],
            'brand_ids' => ['nullable', 'array'],
            'department_ids' => ['nullable', 'array'],
            'category_ids' => ['nullable', 'array'],
            'promoter_ids' => ['nullable', 'array'],
            'group_ids' => ['nullable', 'array'],
            'report_type' => ['required', 'integer'],
            'filter_by' => ['nullable', 'integer'],
        ];
    }
}
