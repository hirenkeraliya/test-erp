<?php

declare(strict_types=1);

namespace App\Domains\Store\DataObjects;

use Spatie\LaravelData\Data;

class MemberAppStoreListData extends Data
{
    public function __construct(
        public ?string $search_text,
        public ?int $per_page,
        public ?int $page,
        public ?string $sort_by,
        public ?string $sort_direction,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'search_text' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,name,code'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ];
    }
}
