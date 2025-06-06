<?php

declare(strict_types=1);

namespace App\Domains\User\DataObjects;

use Spatie\LaravelData\Data;

class CompanyOwnerStyleChartApiData extends Data
{
    public function __construct(
        public ?int $brand_id,
        public ?bool $refresh,
        public ?int $month,
        public ?int $year,
        public ?int $quarter,
    ) {
    }

    public static function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
            'month' => ['sometimes', 'nullable', 'integer', 'between:1,12'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'quarter' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
