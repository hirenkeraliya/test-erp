<?php

declare(strict_types=1);

namespace App\Domains\Counter\DataObjects;

use App\Domains\Counter\CounterQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class CounterData extends Data
{
    public function __construct(
        public string $name,
        public int $location_id,
        public bool $is_locked,
        public bool $is_self_checkout,
    ) {
    }

    /**
     * @return array<string, array<(Exists|Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        $counterId = null;
        $counterQueries = new CounterQueries();
        $locationQueries = new LocationQueries();

        if ('admin.counters.update' === $request->route()?->getName()) {
            $counterId = $request->route()->parameter('counterId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('counters', 'name')->ignore($counterId)
                    ->where($counterQueries->filterByLocation((int) $request->input('location_id'))),
            ],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where(
                        $locationQueries->filterByCompanyAndTypeId(session(
                            'admin_company_id'
                        ), LocationTypes::STORE->value)
                    ),
            ],
            'is_locked' => ['required', 'boolean'],
            'is_self_checkout' => ['required', 'boolean'],
        ];
    }
}
