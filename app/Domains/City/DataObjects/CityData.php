<?php

declare(strict_types=1);

namespace App\Domains\City\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class CityData extends Data
{
    public function __construct(
        public int $country_id,
        public int $state_id,
        public string $name,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $cityId = null;

        if ('admin.cities.update' === $request->route()?->getName()) {
            $cityId = $request->route()->parameter('cityId');
        }

        return [
            'country_id' => ['integer', Rule::exists('countries', 'id')],
            'state_id' => ['integer', Rule::exists('states', 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique('cities', 'name')->ignore($cityId)],
        ];
    }
}
