<?php

declare(strict_types=1);

namespace App\Domains\Country;

use App\Domains\Company\CompanyQueries;
use App\Domains\Country\DataObjects\CountryData;
use App\Models\Country;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CountryQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function getColumnId(): string
    {
        return 'id';
    }

    public function getIdByName(string $name): int
    {
        /** @var Country $country */
        $country = Country::where('name', $name)->first();

        return $country->id;
    }

    public function checkCodeExists(string $code): int
    {
        /** @var Country $country */
        $country = Country::query()
            ->where('iso2', $code)
            ->orWhere('iso3', $code)
            ->first();

        return $country->id;
    }

    public function checkNameExists(string $name): ?int
    {
        $country = Country::query()
            ->select('id', 'name')
            ->where('name', 'LIKE', $name . '%')
            ->first();

        if (! $country instanceof Country) {
            return null;
        }

        return $country->id;
    }

    public function existsByName(string $name): bool
    {
        return Country::select('id')
            ->where('name', $name)
            ->exists();
    }

    public function getList(): Collection
    {
        return Country::query()
            ->select('id', 'name')
            ->get();
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $companyQueries = resolve(CompanyQueries::class);

        return fn ($query) => $query->select('id')
            ->whereHas('company', $companyQueries->filterById($companyId));
    }

    public function getCountryForEcommerce(array $filterData): LengthAwarePaginator
    {
        return Country::select('id', 'name')
            ->with(['states', 'states.cities'])
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function existsById(int $id): bool
    {
        return (bool) Country::select('id')
            ->find($id);
    }

    public function getCountryNameById(int $countryId): string
    {
        return Country::select('name')
            ->findOrFail($countryId)
            ->name;
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return $this->countryQuery($filterData)->paginate($filterData['per_page']);
    }

    public function getCountryExport(array $filterData): Collection
    {
        return $this->countryQuery($filterData)->get();
    }

    public function addNew(CountryData $countryData): void
    {
        $countryDetails = $countryData->all();
        Country::create($countryDetails);
    }

    public function getById(int $countryId): Country
    {
        return Country::select('id', 'iso2', 'name', 'phone_code', 'iso3', 'region', 'subregion')
            ->findOrFail($countryId);
    }

    public function update(CountryData $countryData, int $countryId): void
    {
        $country = $this->getById($countryId);
        $country->update($countryData->all());
    }

    public function getCountryIso2ById(int $countryId): string
    {
        return Country::select('iso2')
            ->findOrFail($countryId)
            ->iso2;
    }

    private function countryQuery(array $filterData): Builder
    {
        return Country::query()
            ->select('id', 'name', 'iso2', 'phone_code', 'iso3', 'region', 'subregion')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'iso2', 'phone_code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getAllCountries(): Collection
    {
        return Country::select('id', 'name')
            ->where('status', true)
            ->get();
    }
}
