<?php

declare(strict_types=1);

namespace App\Domains\Cashback\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiCashbackData extends Data
{
    public function __construct(
        public int $page,
        public string $selected_date,
        public ?int $per_page = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?string $store_ids = null,
        public ?string $location_ids = null,
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
            'selected_date' => ['required', 'date', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => [
                'sometimes',
                'nullable',
                'string',
                'in:id,name,flat_amount,minimum_spend_amount, start_date, end_date',
            ],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'store_ids' => ['sometimes', 'nullable', 'string'],
            'location_ids' => ['sometimes', 'nullable', 'string'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
