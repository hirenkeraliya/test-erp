<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\DataObjects;

use Spatie\LaravelData\Data;

class PromoterCommissionData extends Data
{
    public function __construct(
        public string $start_date,
        public string $end_date,
        public int $per_page,
        public int $page,
        public ?int $store_id,
        public ?int $location_id,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'per_page' => ['required', 'integer'],
            'page' => ['required', 'integer'],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before:' . now()->startOfMonth()->format('Y-m-d'),
            ],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before:' . now()->startOfMonth()->format('Y-m-d'),
            ],
            'sort_by' => ['sometimes', 'string', 'in:id'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ];
    }
}
