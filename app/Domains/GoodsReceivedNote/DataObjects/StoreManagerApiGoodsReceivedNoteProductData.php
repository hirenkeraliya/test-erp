<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiGoodsReceivedNoteProductData extends Data
{
    public function __construct(
        public int $id,
        public ?int $store_id,
        public ?int $location_id,
        public int $page,
        public int $per_page,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'per_page' => ['required', 'integer'],
            'page' => ['required', 'integer'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
