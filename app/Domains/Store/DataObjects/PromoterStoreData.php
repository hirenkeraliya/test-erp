<?php

declare(strict_types=1);

namespace App\Domains\Store\DataObjects;

use Spatie\LaravelData\Data;

class PromoterStoreData extends Data
{
    public function __construct(
        public ?int $per_page = null,
        public ?int $page = null,
        public ?string $sort_by = null,
        public ?string $sort_direction = null,
        public ?string $search_text = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
