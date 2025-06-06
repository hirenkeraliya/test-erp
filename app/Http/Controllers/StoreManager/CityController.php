<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\City\CityQueries;
use App\Http\Controllers\Controller;

class CityController extends Controller
{
    public function __construct(
        protected CityQueries $cityQueries
    ) {
    }

    public function getCitiesByStateId(int $stateId): array
    {
        $cities = $this->cityQueries->getByStateId($stateId);
        $cities = $cities->map(fn ($city): array => [
            'id' => $city->id,
            'name' => $city->name,
        ]);

        return [
            'cities' => $cities,
        ];
    }
}
