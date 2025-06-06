<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\DataObjects;

use Spatie\LaravelData\Data;

class PaginatedMemberGroupListDataForPos extends Data
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
            'sort_by' => ['sometimes', 'string', 'in:id,name,code'],
            'search_text' => ['sometimes', 'string'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
