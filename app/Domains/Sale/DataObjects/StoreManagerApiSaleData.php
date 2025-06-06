<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiSaleData extends Data
{
    public function __construct(
        public int $page,
        public int $per_page,
        public ?int $store_id,
        public ?int $location_id,
        public string $start_date,
        public string $end_date,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?int $member_id = null,
        public ?int $employee_id = null,
        public ?int $counter_id = null,
        public ?int $cashier_id = null,
        public ?string $search_text = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'page' => ['required', 'integer'],
            'per_page' => ['required', 'integer'],
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort_by' => ['sometimes', 'string', 'in:id'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'member_id' => ['sometimes', 'integer'],
            'employee_id' => ['sometimes', 'integer'],
            'counter_id' => ['sometimes', 'integer'],
            'cashier_id' => ['sometimes', 'integer'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
