<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class PromoterCommissionCustomReportData extends Data
{
    public function __construct(
        public array $month_range,
        public array $location_ids = [],
        public ?array $brand_ids = null,
        public ?array $department_ids = null,
        public ?array $group_ids = null,
        public ?int $filter_by = null,
        public ?int $report_type = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['required', 'array'],
            'brand_ids' => ['nullable', 'array'],
            'department_ids' => ['nullable', 'array'],
            'group_ids' => ['nullable', 'array'],
            'month_range' => ['required', 'array'],
            'filter_by' => ['nullable', 'integer'],
            'report_type' => ['required', 'integer'],
        ];
    }
}
