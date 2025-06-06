<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use App\Models\Employee;
use App\Models\Member;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PendingLayawaySalesDataForPos extends Data
{
    public function __construct(
        public ?int $member_id,
        public ?int $employee_id,
        public ?string $from_date,
        public ?string $to_date,
        public ?string $search_text,
        public ?string $after_updated_at,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'integer', Rule::exists(Member::class, 'id')],
            'employee_id' => ['sometimes', 'integer', Rule::exists(Employee::class, 'id')],
            'from_date' => ['sometimes', 'string', 'date_format:Y-m-d'],
            'to_date' => ['sometimes', 'string', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'search_text' => ['sometimes', 'string'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
