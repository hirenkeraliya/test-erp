<?php

declare(strict_types=1);

namespace App\Domains\City;

use App\Domains\City\DataObjects\CityData;
use App\Domains\Country\CountryQueries;
use App\Domains\State\StateQueries;
use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CityQueries
{
    public function getByStateId(int $stateId): Collection
    {
        return City::select('id', 'name')
            ->where('state_id', $stateId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function existsByName(string $name): bool
    {
        return City::select('id')
            ->where('name', $name)
            ->exists();
    }

    public function existsById(int $id): bool
    {
        return (bool) City::select('id')
            ->find($id);
    }

    public function getIdByName(string $name): int
    {
        /** @var City $city */
        $city = City::where('name', $name)->first();

        return $city->id;
    }

    public function checkNameExists(string $name): ?int
    {
        $city = City::query()
            ->select('id')
            ->where('name', 'LIKE', $name . '%')
            ->first();

        if (! $city instanceof City) {
            return null;
        }

        return $city->id;
    }

    public function getCityNameById(int $cityId): string
    {
        return City::select('name')
            ->findOrFail($cityId)
            ->name;
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return $this->cityQuery($filterData)->paginate($filterData['per_page']);
    }

    public function getCityExport(array $filterData): Collection
    {
        return $this->cityQuery($filterData)->get();
    }

    public function addNew(CityData $cityData): void
    {
        $countryQueries = resolve(CountryQueries::class);
        $countryCode = $countryQueries->getCountryIso2ById($cityData->country_id);
        $cityDetails = $cityData->all();
        $cityDetails['country_code'] = $countryCode;
        City::create($cityDetails);
    }

    public function getById(int $cityId): City
    {
        return City::select('id', 'name', 'country_id', 'country_code', 'state_id')
            ->findOrFail($cityId);
    }

    public function update(CityData $cityData, int $cityId): void
    {
        $countryQueries = resolve(CountryQueries::class);
        $countryCode = $countryQueries->getCountryIso2ById($cityData->country_id);

        $city = $this->getById($cityId);

        $cityDetails = $cityData->all();
        $cityDetails['country_code'] = $countryCode;
        $city->update($cityDetails);
    }

    private function cityQuery(array $filterData): Builder
    {
        $countryQueries = resolve(CountryQueries::class);
        $stateQueries = resolve(StateQueries::class);

        return City::query()
            ->select('id', 'name', 'country_id', 'state_id', 'country_code')
            ->with([
                'country:' . $countryQueries->getBasicColumnNames(),
                'state:' . $stateQueries->getBasicColumnNames(),
            ])
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'country_code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getAllCities(int $perPage = 5000): LengthAwarePaginator
    {
        return City::select('id', 'name', 'state_id', 'country_id')
            ->paginate($perPage);
    }
}
