<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiStoreStockData extends Data
{
    public function __construct(
        public int $page,
        public ?int $per_page = null,
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
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,stock'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
