<?php

declare(strict_types=1);

namespace App\Domains\User\DataObjects;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class CompanyOwnerRevenueViewApiData extends Data
{
    public function __construct(
        public ?string $start_date,
        public ?string $end_date,
        public ?int $brand_id,
        public ?bool $refresh
    ) {
    }

    public static function rules(Request $request): array
    {
        return [
            'start_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => [
                'sometimes',
                'nullable',
                'date',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($request): void {
                    if ($request->input('start_date') && $value && $value < $request->input('start_date')) {
                        $fail('The end date must be a date after the start date.');
                    }
                },
            ],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
