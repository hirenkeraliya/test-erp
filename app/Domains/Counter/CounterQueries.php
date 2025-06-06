<?php

declare(strict_types=1);

namespace App\Domains\Counter;

use App\Domains\Counter\DataObjects\CounterData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Models\Counter;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CounterQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->counterQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getByIds(array $counterIds): Collection
    {
        return Counter::select('name')
            ->whereIntegerInRaw('id', $counterIds)
            ->get();
    }

    public function getAppVersionCounts(int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return Counter::select('app_version', DB::raw('COUNT(*) as count'))
            ->whereHas('location', $locationQueries->filterByCompany($companyId))
            ->whereNotNull('app_version')
            ->whereNot('app_version', '')
            ->groupBy('app_version')
            ->get();
    }

    public function addNew(CounterData $counterData): void
    {
        Counter::create($counterData->all());
    }

    public function getById(int $counterId, int $companyId): Counter
    {
        $locationQueries = new LocationQueries();

        return Counter::select(
            'id',
            'name',
            'location_id',
            'is_locked',
            'is_self_checkout',
            'counter_update_id',
            'app_version'
        )
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->findOrFail($counterId);
    }

    public function getAllByCompanyId(int $companyId): Collection
    {
        $locationQueries = new LocationQueries();

        return Counter::select('id', 'name', 'location_id', 'app_version', 'app_version_updated_at')
            ->with(['location:' . $locationQueries->getBasicColumnNames()])
            ->whereHas('location', $locationQueries->filterByCompany($companyId))
            ->get();
    }

    public function setCounterUpdateId(Counter $counter, int $counterUpdateId): void
    {
        $counter->counter_update_id = $counterUpdateId;
        $counter->save();
    }

    public function setCounterAppVersion(Counter $counter, string $appVersion): void
    {
        $counter->app_version = $appVersion;
        $counter->app_version_updated_at = Carbon::now()->format('Y-m-d H:i:s');
        $counter->save();
    }

    public function unsetCounterUpdateId(Counter $counter): void
    {
        $counter->counter_update_id = null;
        $counter->save();
    }

    public function update(CounterData $counterData, int $counterId, int $companyId): void
    {
        $counter = $this->getById($counterId, $companyId);
        $counter->update($counterData->all());
    }

    public function filterByLocation(int $locationId): Closure
    {
        return fn ($query) => $query->select('id')->where('location_id', $locationId);
    }

    public function filterById(int $counterId): Closure
    {
        return fn ($query) => $query->select('id')->where('id', $counterId);
    }

    public function filterByIds(array $counterIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('id', $counterIds);
    }

    public function filterByCompanyId(int $companyId): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id', 'location_id')
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value));
    }

    public function filterByLocationId(int $locationId): Closure
    {
        return fn ($query) => $query->where('location_id', $locationId);
    }

    public function filterByLocations(array $locationIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('location_id', $locationIds);
    }

    public function filterByCounterUpdateId(int $counterUpdateId): Closure
    {
        return fn ($query) => $query->select('id')->where('counter_update_id', $counterUpdateId);
    }

    public function getDetailsWithCounterUpdateByCounterUpdateId(int $counterUpdateId): Counter
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return Counter::select('id', 'counter_update_id', 'name', 'is_locked', 'is_self_checkout')
            ->with('counterUpdate:' . $counterUpdateQueries->getBasicColumnNames())
            ->where('counter_update_id', $counterUpdateId)
            ->firstOrFail();
    }

    public function getCounterBasicColumnNames(): string
    {
        return 'id,location_id,name,is_locked,created_at,is_self_checkout';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,location_id';
    }

    public function getLocationIdColumn(): string
    {
        return 'id,location_id';
    }

    public function searchByNameAndLocationName(string $searchText): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id')->where(function ($query) use (
            $searchText,
            $locationQueries
        ): void {
            $query->where('name', 'like', '%' . $searchText . '%')
                ->orWhereHas('location', $locationQueries->searchByName($searchText));
        });
    }

    public function getCountByLocation(int $locationId): int
    {
        return Counter::where('location_id', $locationId)->count();
    }

    public function getCountByOpenCounterForLocation(int $locationId): int
    {
        return Counter::where('location_id', $locationId)
            ->whereNotNull('counter_update_id')
            ->count();
    }

    public function getByCounterUpdateId(int $counterUpdateId): Counter
    {
        return Counter::query()
            ->select('id', 'counter_update_id')
            ->where('counter_update_id', $counterUpdateId)
            ->firstOrFail();
    }

    public function getCounterListOfSelectedLocation(
        int $locationId,
        int $companyId,
        ?string $searchText = null
    ): Collection {
        $locationQueries = resolve(LocationQueries::class);

        return Counter::query()
            ->select('id', 'name', 'location_id', 'is_locked', 'counter_update_id', 'is_self_checkout')
            ->where('location_id', $locationId)
            ->when(null !== $searchText, function ($query) use ($searchText): void {
                $query->where('name', 'like', '%' . $searchText . '%');
            })
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->get();
    }

    public function getCountersOfLocations(array $locationIds, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return Counter::query()
            ->select('id', 'name')
            ->whereIntegerInRaw('location_id', $locationIds)
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->get();
    }

    public function existsByName(string $name, int $locationId): bool
    {
        return Counter::whereCaseSensitive('name', $name)->where('location_id', $locationId)->exists();
    }

    public function getCountersExport(array $filterData, int $companyId): Collection
    {
        return $this->counterQuery($filterData, $companyId)->get();
    }

    public function filterByLocationAndCompany(int $locationId, int $companyId): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id', 'location_id')
            ->where('location_id', $locationId)
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value));
    }

    public function filterByCompanyIdAndStoreId(int $companyId, ?int $locationId = null): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id', 'location_id')
            ->when($locationId, function ($query) use ($locationId): void {
                $query->where('location_id', $locationId);
            })
            ->whereHas('location', $locationQueries->filterByCompany($companyId));
    }

    public function filterByLocationsAndCompany(array $locationId, int $companyId): Closure
    {
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id', 'location_id')
            ->where('location_id', $locationId)
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value));
    }

    public function counterExists(string $name, int $companyId): bool
    {
        $locationQueries = resolve(LocationQueries::class);

        return Counter::whereCaseSensitive('name', $name)
            ->whereHas('location', $locationQueries->filterByCompany($companyId))
            ->exists();
    }

    public function updateByName(array $counterData, string $name): void
    {
        $counter = Counter::select('id')->where('name', $name)
            ->first();

        if ($counter instanceof Counter) {
            $counter->update($counterData);
        }
    }

    private function counterQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);

        return Counter::query()
            ->with(['location:' . $locationQueries->getBasicColumnNames()])
            ->select(
                'id',
                'name',
                'location_id',
                'is_locked',
                'app_version',
                'app_version_updated_at',
                'is_self_checkout'
            )
            ->when($filterData['search_text'], function ($query) use ($filterData, $locationQueries): void {
                $query->where(function ($query) use ($filterData, $locationQueries): void {
                    $query
                        ->whereAny(['name', 'app_version'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('location', $locationQueries->searchByName($filterData['search_text']));
                });
            })
            ->whereHas('location', $locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getCounterNameForFilter(array $counterIds): string
    {
        $counterData = [];
        $counter = Counter::select('name')
            ->whereIntegerInRaw('id', values: $counterIds)
            ->get();

        if ($counter->isNotEmpty()) {
            $counterData = $counter->pluck('name')->toArray();
        }

        return implode(', ', $counterData);
    }
}
