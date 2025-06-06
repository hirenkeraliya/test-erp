<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Counter;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PaginatedRegularAndCompletedSalesDataForPos extends Data
{
    public function __construct(
        public ?int $page,
        public ?int $member_id,
        public ?int $employee_id,
        public ?int $counter_id,
        public ?string $from_date,
        public ?string $to_date,
        public ?int $per_page,
        public ?string $sort_by,
        public ?string $sort_direction,
        public ?string $search_text,
        public ?string $after_updated_at,
        public ?int $status_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer'],
            'member_id' => ['sometimes', 'integer', Rule::exists(Member::class, 'id')],
            'employee_id' => ['sometimes', 'integer', Rule::exists(Employee::class, 'id')],
            'counter_id' => ['sometimes', 'integer', Rule::exists(Counter::class, 'id')],
            'from_date' => ['sometimes', 'string', 'date_format:Y-m-d'],
            'to_date' => ['sometimes', 'string', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'per_page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,user_id,offline_sale_id'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'string'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'status_id' => ['sometimes', 'nullable', 'integer', 'in:' . SaleStatus::getValues()],
        ];
    }
}
