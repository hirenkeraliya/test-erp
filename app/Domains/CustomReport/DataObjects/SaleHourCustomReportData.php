<?php

declare(strict_types=1);

namespace App\Domains\CustomReport\DataObjects;

use Spatie\LaravelData\Data;

class SaleHourCustomReportData extends Data
{
    public function __construct(
        public array $date_range = [],
        public ?int $location_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'date_range' => ['required', 'array'],
            'location_id' => ['nullable', 'integer'],
        ];
    }
}
