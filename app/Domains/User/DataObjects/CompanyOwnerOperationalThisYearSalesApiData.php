<?php

declare(strict_types=1);

namespace App\Domains\User\DataObjects;

use Spatie\LaravelData\Data;

class CompanyOwnerOperationalThisYearSalesApiData extends Data
{
    public function __construct(
        public ?string $date,
        public ?int $location_id,
        public ?int $brand_id,
        public ?bool $refresh
    ) {
    }

    public static function rules(): array
    {
        return [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
