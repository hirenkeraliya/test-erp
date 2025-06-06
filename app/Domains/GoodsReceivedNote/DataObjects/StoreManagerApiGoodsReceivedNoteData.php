<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiGoodsReceivedNoteData extends Data
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
            'sort_by' => [
                'sometimes',
                'string',
                'in:id,created_at,grn_reference,delivery_order_reference,purchase_order_reference,vendor',
            ],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
