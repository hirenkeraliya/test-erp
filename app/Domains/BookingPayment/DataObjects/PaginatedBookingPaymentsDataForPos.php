<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\DataObjects;

use App\Models\Member;
use App\Models\Promoter;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PaginatedBookingPaymentsDataForPos extends Data
{
    public function __construct(
        public ?int $member_id,
        public ?int $promoter_id,
        public ?string $status,
        public ?string $from_date,
        public ?string $to_date,
        public ?string $sort_by,
        public ?string $sort_direction,
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
            'promoter_id' => ['sometimes', 'integer', Rule::exists(Promoter::class, 'id')],
            'status' => ['sometimes', 'string'],
            'from_date' => ['sometimes', 'string', 'date_format:Y-m-d'],
            'to_date' => ['sometimes', 'string', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'sort_by' => ['sometimes', 'string', 'in:id,offline_id'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'search_text' => ['sometimes', 'string'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
