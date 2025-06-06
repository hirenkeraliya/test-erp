<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class CashMovementCustomReportData extends Data
{
    public function __construct(
        public ?array $location_ids = null,
        public ?array $date_range = [],
        public ?array $counter_ids = null,
        public ?array $cashier_ids = null,
        public ?int $filter_by = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'date_range' => ['required', 'array'],
            'counter_ids' => ['nullable', 'array'],
            'cashier_ids' => ['nullable', 'array'],
            'filter_by' => ['nullable', 'integer'],
        ];
    }
}
