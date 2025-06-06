<?php

declare(strict_types=1);

namespace App\Domains\User\DataObjects;

use Spatie\LaravelData\Data;

class CompanyOwnerThisMonthTopSellingColorsApiData extends Data
{
    public function __construct(
        public ?int $location_id,
        public ?int $brand_id,
        public ?bool $refresh
    ) {
    }

    public static function rules(): array
    {
        return [
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
