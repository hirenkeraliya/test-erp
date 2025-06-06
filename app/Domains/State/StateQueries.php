<?php

declare(strict_types=1);

namespace App\Domains\State;

use App\Domains\Country\CountryQueries;
use App\Domains\State\DataObjects\StateData;
use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StateQueries
{
    public function getByCountryId(int $countryId): Collection
    {
        return State::select('id', 'name')
            ->where('country_id', $countryId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name';
    }

    public function getAllColumns(): string
    {
        return 'id,country_id,name';
    }

    public function existsByName(string $name): bool
    {
        return State::select('id')
            ->where('name', $name)
            ->exists();
    }

    public function getIdByName(string $name): int
    {
        /** @var State $state */
        $state = State::where('name', $name)->first();

        return $state->id;
    }

    public function checkNameExists(string $name): ?int
    {
        $state = State::query()
            ->select('id')
            ->where('name', 'LIKE', $name . '%')
            ->first();

        if (! $state instanceof State) {
            return null;
        }

        return $state->id;
    }

    public function existsById(int $id): bool
    {
        return (bool) State::select('id')
            ->find($id);
    }

    public function getStateNameById(int $stateId): string
    {
        return State::select('name')
            ->findOrFail($stateId)
            ->name;
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return $this->stateQuery($filterData)->paginate($filterData['per_page']);
    }

    public function getStateExport(array $filterData): Collection
    {
        return $this->stateQuery($filterData)->get();
    }

    public function addNew(StateData $stateData): void
    {
        $countryQueries = resolve(CountryQueries::class);
        $countryCode = $countryQueries->getCountryIso2ById($stateData->country_id);
        $stateDetails = $stateData->all();
        $stateDetails['country_code'] = $countryCode;
        State::create($stateDetails);
    }

    public function getById(int $stateId): State
    {
        return State::select('id', 'name', 'country_id', 'country_code')
            ->findOrFail($stateId);
    }

    public function update(StateData $stateData, int $stateId): void
    {
        $state = $this->getById($stateId);
        $countryQueries = resolve(CountryQueries::class);
        $countryCode = $countryQueries->getCountryIso2ById($stateData->country_id);

        $stateDetails = $stateData->all();
        $stateDetails['country_code'] = $countryCode;

        $state->update($stateDetails);
    }

    private function stateQuery(array $filterData): Builder
    {
        $countryQueries = resolve(CountryQueries::class);

        return State::query()
            ->select('id', 'name', 'country_id', 'country_code')
            ->with(['country:' . $countryQueries->getBasicColumnNames()])
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

    public function getAllStates(): Collection
    {
        return State::select('id', 'name', 'country_id')
            ->get();
    }
}
