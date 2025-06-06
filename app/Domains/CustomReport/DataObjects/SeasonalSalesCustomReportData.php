<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class SeasonalSalesCustomReportData extends Data
{
    public function __construct(
        public int $report_type_id,
        public int $sale_season_id,
        public ?int $compare_sale_season_id = null,
        public ?array $brand_ids = null,
        public ?array $location_ids = null,
        public ?array $sale_season_date_range = [],
        public ?array $sale_season_compare_date_range = [],
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
            'report_type_id' => ['required', 'integer'],
            'sale_season_id' => ['required', 'integer'],
            'compare_sale_season_id' => ['nullable', 'integer'],
            'sale_season_date_range' => ['nullable', 'array'],
            'sale_season_compare_date_range' => ['nullable', 'array'],
        ];
    }
}
