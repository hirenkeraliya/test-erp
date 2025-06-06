<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Common;

use App\Domains\Country\CountryQueries;
use App\Http\Controllers\Controller;

class CountryController extends Controller
{
    public function getAllCountries(): array
    {
        $countryQueries = resolve(CountryQueries::class);

        return [
            'countries' => $countryQueries->getAllCountries(),
        ];
    }
}
