<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\DataObjects;

use Spatie\LaravelData\Data;

class PaginatedMemberGroupListDataForEcommerce extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?int $page,
        public ?string $sort_by,
        public ?string $sort_direction,
        public ?string $search_text,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'nullable', 'integer'],
            'page' => ['sometimes', 'nullable', 'integer'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name,code'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
