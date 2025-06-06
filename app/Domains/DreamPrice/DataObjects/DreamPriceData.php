<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\DataObjects;

use App\Domains\DreamPrice\DreamPriceQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class DreamPriceData extends Data
{
    public function __construct(
        public string $name,
        public array $location_ids,
        public string $start_date,
        public string $end_date,
        public bool $allow_registered_member,
        public bool $allow_employee,
        public bool $allow_walk_in_member,
        public bool $is_available_in_ecommerce,
        public ?array $member_group_ids,
        public ?array $employee_group_ids,
        public bool $is_available_in_pos = true,
        public ?array $sale_channel_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $dreamPriceId = null;
        $dreamPriceQueries = new DreamPriceQueries();

        if ('admin.dream_prices.update' === $request->route()?->getName()) {
            $dreamPriceId = $request->route()->parameter('dreamPriceId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dream_prices', 'name')->ignore($dreamPriceId)
                    ->where($dreamPriceQueries->filterByCompany(session('admin_company_id'))),
            ],
            'allow_registered_member' => ['required', 'boolean'],
            'allow_employee' => ['required', 'boolean'],
            'allow_walk_in_member' => ['required', 'boolean'],
            'is_available_in_pos' => ['required', 'boolean'],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'start_date' => ['required', 'date', 'max:255', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'max:255', 'after:start_date', 'date_format:Y-m-d'],
            'member_group_ids' => ['nullable', 'array'],
            'member_group_ids.*' => ['integer'],
            'employee_group_ids' => ['nullable', 'array'],
            'employee_group_ids.*' => ['integer'],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
        ];
    }
}
