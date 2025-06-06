<?php

declare(strict_types=1);

namespace App\Domains\Promotion\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiPromotionData extends Data
{
    public function __construct(
        public int $page,
        public ?int $store_id = null,
        public ?int $location_id = null,
        public ?int $per_page = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?string $search_text = null,
        public ?string $after_updated_at = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'page' => ['required', 'integer'],
            'store_id' => ['sometimes', 'nullable', 'integer'],
            'location_id' => ['sometimes', 'nullable', 'integer'],
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name,start_date,end_date,start_time,end_time'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
