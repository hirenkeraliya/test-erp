<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiCloseCounterData extends Data
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
        public ?array $counter_ids = null,
        public ?int $cashier_id = null,
        public ?string $closed_at = null,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'page' => ['required', 'integer'],
            'per_page' => ['required', 'integer'],
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort_by' => ['sometimes', 'string', 'in:id,counter_id'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'counter_ids' => ['sometimes', 'nullable', 'array'],
            'cashier_id' => ['sometimes', 'nullable', 'integer'],
            'closed_at' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
