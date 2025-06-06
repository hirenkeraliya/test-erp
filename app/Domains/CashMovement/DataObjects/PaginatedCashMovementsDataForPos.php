<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\DataObjects;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class PaginatedCashMovementsDataForPos extends Data
{
    public function __construct(
        public ?int $page,
        public ?string $from_date,
        public ?string $to_date,
        public ?int $per_page,
        public ?bool $only_current_counter,
        public ?int $movement_type_id,
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
            'page' => ['sometimes', 'integer'],
            'from_date' => ['sometimes', 'string', 'date_format:Y-m-d'],
            'to_date' => ['sometimes', 'string', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'per_page' => ['sometimes', 'integer'],
            'only_current_counter' => ['sometimes', 'boolean'],
            'movement_type_id' => ['sometimes', 'integer', new Enum(CashMovementTypes::class)],
            'sort_by' => ['sometimes', 'string', 'in:id'],
            'search_text' => ['sometimes', 'string'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
