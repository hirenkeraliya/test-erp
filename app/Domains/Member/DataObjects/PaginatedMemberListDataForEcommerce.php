<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use Spatie\LaravelData\Data;

class PaginatedMemberListDataForEcommerce extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?int $page,
        public ?string $sort_by,
        public ?string $sort_direction,
        public ?string $search_text,
        public ?string $mobile_number,
        public ?string $email,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,first_name,email,card_number'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'nullable', 'string'],
            'mobile_number' => ['sometimes', 'string', 'nullable'],
            'email' => ['sometimes', 'string', 'nullable'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
