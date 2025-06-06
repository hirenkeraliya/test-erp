<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class StockTransferStatusSummaryCustomReportData extends Data
{
    public function __construct(
        public ?array $location_ids = null,
        public ?array $date_range = [],
        public ?int $report_type = null,
        public ?int $status = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['required', 'string'],
            'date_range' => ['required', 'array'],
            'report_type' => ['required', 'integer'],
            'status' => ['required', 'integer'],
        ];
    }
}
