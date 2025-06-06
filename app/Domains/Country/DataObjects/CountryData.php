<?php

declare(strict_types=1);

namespace App\Domains\Country\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class CountryData extends Data
{
    public function __construct(
        public string $iso2,
        public string $name,
        public string $phone_code,
        public string $iso3,
        public string $region,
        public ?string $subregion,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $countryId = null;

        if ('admin.countries.update' === $request->route()?->getName()) {
            $countryId = $request->route()->parameter('countryId');
        }

        return [
            'iso2' => ['required', 'string', 'max:2'],
            'name' => ['required', 'string', 'max:255', Rule::unique('countries', 'name')->ignore($countryId)],
            'phone_code' => ['required', 'string', 'max:5'],
            'iso3' => ['required', 'string', 'max:3'],
            'region' => ['required', 'string', 'max:255'],
            'subregion' => ['required', 'string', 'max:255'],
        ];
    }
}
