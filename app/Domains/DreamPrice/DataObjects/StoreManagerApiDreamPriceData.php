<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiDreamPriceData extends Data
{
    public function __construct(
        public int $page,
        public string $selected_date,
        public ?int $per_page = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?string $search_text = null,
        public ?int $store_id = null,
        public ?int $location_id = null,
        public ?string $dream_price_ids = null,
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
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name,start_date,end_date'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
            'store_id' => ['sometimes', 'nullable', 'integer'],
            'location_id' => ['sometimes', 'nullable', 'integer'],
            'dream_price_ids' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
