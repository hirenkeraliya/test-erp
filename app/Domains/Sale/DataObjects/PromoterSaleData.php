<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use Spatie\LaravelData\Data;

class PromoterSaleData extends Data
{
    public function __construct(
        public ?int $store_id,
        public ?int $location_id,
        public string $start_date,
        public string $end_date,
        public ?int $per_page = null,
        public ?int $page = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ];
    }
}
