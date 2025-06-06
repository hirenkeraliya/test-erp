<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class SaleOverallByStoreCustomReportData extends Data
{
    public function __construct(
        public int $report_by,
        public array $date_range = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'date_range' => ['required', 'array'],
            'report_by' => ['required', 'integer'],
        ];
    }
}
