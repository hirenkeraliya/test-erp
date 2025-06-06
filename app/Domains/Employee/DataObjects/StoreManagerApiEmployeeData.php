<?php

declare(strict_types=1);

namespace App\Domains\Employee\DataObjects;

use Spatie\LaravelData\Data;

class StoreManagerApiEmployeeData extends Data
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
            'per_page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,first_name,last_name'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
