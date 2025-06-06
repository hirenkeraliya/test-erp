<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Common;

use App\Domains\City\CityQueries;
use App\Http\Controllers\Controller;

class CityController extends Controller
{
    public function getAllCities(): array
    {
        $cityQueries = resolve(CityQueries::class);

        return [
            'cities' => $cityQueries->getAllCities(),
        ];
    }
}
