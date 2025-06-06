<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class VoidReportCustomReportData extends Data
{
    public function __construct(
        public array $date_range = [],
        public ?array $location_ids = [],
        public ?array $counter_ids = null,
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
            'counter_ids' => ['nullable', 'array'],
            'date_range' => ['required', 'array'],
            'filter_by' => ['nullable', 'integer'],
        ];
    }
}
