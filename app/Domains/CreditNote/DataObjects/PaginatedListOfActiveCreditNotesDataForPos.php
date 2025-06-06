<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\DataObjects;

use App\Models\Employee;
use App\Models\Member;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PaginatedListOfActiveCreditNotesDataForPos extends Data
{
    public function __construct(
        public ?int $per_page,
        public ?int $page,
        public ?int $member_id,
        public ?int $employee_id,
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
            'member_id' => ['sometimes', 'integer', Rule::exists(Member::class, 'id')],
            'employee_id' => ['sometimes', 'integer', Rule::exists(Employee::class, 'id')],
            'sort_by' => ['sometimes', 'string', 'in:id'],
            'search_text' => ['sometimes', 'string'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
