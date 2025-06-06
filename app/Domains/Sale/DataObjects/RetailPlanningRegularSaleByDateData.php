<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use Spatie\LaravelData\Data;

class RetailPlanningRegularSaleByDateData extends Data
{
    public function __construct(
        public ?array $dates = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'dates' => ['nullable', 'array'],
            'dates.start_date' => ['nullable', 'date_format:Y-m-d'],
            'dates.end_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
