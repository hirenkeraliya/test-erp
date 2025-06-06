<?php

declare(strict_types=1);

namespace App\Domains\User\DataObjects;

use Spatie\LaravelData\Data;

class CompanyOwnerSeasonalSalesComparisonChartApiData extends Data
{
    public function __construct(
        public int $sale_season_id,
        public ?int $location_id,
        public ?int $brand_id,
        public int $comparison_x_sale_season_id,
        public int $comparison_y_sale_season_id,
    ) {
    }

    public static function rules(): array
    {
        return [
            'sale_season_id' => ['required', 'integer', 'exists:sale_seasons,id'],
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'comparison_x_sale_season_id' => ['sometimes', 'nullable', 'integer', 'exists:sale_seasons,id'],
            'comparison_y_sale_season_id' => ['sometimes', 'nullable', 'integer', 'exists:sale_seasons,id'],
        ];
    }
}
