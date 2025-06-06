<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\DataObjects;

use Spatie\LaravelData\Data;

class PaginatedEmployeeGroupListDataForPos extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?int $page,
        public ?string $sort_by,
        public ?string $search_text,
        public ?string $sort_direction,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,first_name,email,card_number'],
            'search_text' => ['sometimes', 'string'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
