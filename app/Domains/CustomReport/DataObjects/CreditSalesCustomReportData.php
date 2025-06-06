<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class CreditSalesCustomReportData extends Data
{
    public function __construct(
        public int $report_type,
        public ?array $location_ids = [],
        public array $date_range = [],
        public ?array $counter_ids = null,
        public ?array $cashier_ids = null,
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
            'report_type' => ['required', 'integer'],
        ];
    }
}
