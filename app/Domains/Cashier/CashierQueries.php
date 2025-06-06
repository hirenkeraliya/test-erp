<?php

declare(strict_types=1);

namespace App\Domains\Cashier;

use App\Domains\Cashier\DataObjects\CashierChangePinData;
use App\Domains\Cashier\DataObjects\CashierData;
use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\City\CityQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\StoreManager;
use Carbon\Carbon;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashierQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Cashier::query()
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getNamesAndStatusColumns(),
            ])
            ->select('id', 'employee_id', 'username')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($employeeQueries, $filterData): void {
                $query->where(function ($query) use ($employeeQueries, $filterData): void {
                    $query->where('username', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('employee', $employeeQueries->searchByBasicColumns($filterData['search_text']));
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getByIds(array $cashierIds): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Cashier::select('employee_id')
            ->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->whereIntegerInRaw('id', $cashierIds)
            ->get();
    }

    public function addNew(CashierData $cashierData, Admin|StoreManager $user): void
    {
        $cashierValidatedData = $cashierData->all();
        unset($cashierValidatedData['location_ids']);
        $cashierValidatedData['created_by_type'] = ModelMapping::getCaseName($user::class);
        $cashierValidatedData['created_by_id'] = $user->id;

        $cashier = Cashier::create($cashierValidatedData);
        $cashier->locations()->sync($cashierData->location_ids);
    }

    public function getByIdWithLocations(int $cashierId, int $companyId): Cashier
    {
        $locationQueries = new LocationQueries();
        $employeeQueries = new EmployeeQueries();
        $cashierGroupQueries = new CashierGroupQueries();

        return Cashier::select('id', 'username', 'employee_id', 'cashier_group_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'cashierGroup:' . $cashierGroupQueries->getIdAndPriceOverrideLimitPercentageColumnName(),
                'employee:' . $employeeQueries->getNamesAndStatusColumns(),
            ])
            ->findOrFail($cashierId);
    }

    public function getById(int $cashierId, int $companyId): Cashier
    {
        $employeeQueries = new EmployeeQueries();

        return Cashier::whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($cashierId);
    }

    public function filterById(int $cashierId): Closure
    {
        return fn ($query) => $query->select('id')->where('id', $cashierId);
    }

    public function filterByIds(array $cashierIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('id', $cashierIds);
    }

    public function getList(int $locationId, ?string $afterUpdatedAt = null): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);
        $locationQueries = new LocationQueries();

        return Cashier::select('id', 'employee_id', 'cashier_group_id', 'username', 'pin')
           ->with([
               'employee:' . $employeeQueries->getBasicColumnNames(),
               'cashierGroup:' . $cashierGroupQueries->getBasicColumnNames(),
           ])
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->get();
    }

    public function getAllCashiersByCompany(int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Cashier::select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getFirstAndLastNameColumns()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->get();
    }

    public function getListForStoreManagerApp(array $filterData): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = new LocationQueries();

        return Cashier::select('id', 'employee_id')
           ->with(['employee:' . $employeeQueries->getBasicColumnNames()])
            ->whereHas(
                'locations',
                $locationQueries->filterById((int) $filterData['location_id'], LocationTypes::STORE->value)
            )
            ->when(null !== $filterData['search_text'], function ($query) use (
                $filterData,
                $employeeQueries
            ): void {
                $query->whereHas('employee', $employeeQueries->searchByFirstAndLastName($filterData['search_text']));
            })
            ->get();
    }

    public function update(CashierData $cashierData, int $cashierId, int $companyId): void
    {
        $cashier = $this->getById($cashierId, $companyId);
        $cashierValidatedData = $cashierData->all();
        unset($cashierValidatedData['pin'], $cashierValidatedData['location_ids']);

        $cashier->update($cashierValidatedData);

        $cashier->locations()->sync($cashierData->location_ids);
    }

    public function getByUsernameWithEmployeeDetails(string $username): Cashier
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Cashier::with('employee:' . $employeeQueries->getBasicColumnNamesWithStatus())
            ->select('id', 'username', 'cashier_group_id', 'employee_id', 'pin', 'last_login_at')
            ->whereCaseSensitive('username', $username)
            ->firstOrFail();
    }

    public function checkCompanyAndGetByUsernameWithEmployeeDetails(string $username): ?Cashier
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Cashier::with('employee:' . $employeeQueries->getBasicColumnNamesWithStatus())
            ->whereHas('employee', function ($query): void {
                $query->whereHas('company', function ($companyQuery): void {
                    $companyQuery->whereNull('deleted_at');
                });
            })
            ->select('id', 'username', 'cashier_group_id', 'employee_id', 'pin', 'last_login_at')
            ->whereCaseSensitive('username', $username)
            ->first();
    }

    public function loadDetailsForMeApiEndpoint(Cashier $cashier): Cashier
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $cityQueries = resolve(CityQueries::class);

        return $cashier->load(
            'employee:' . $employeeQueries->getColumnNamesForMeApiEndPoint(),
            'employee.company:' . $companyQueries->getColumnNamesForMeApiEndPoint(),
            'cashierGroup:' . $cashierGroupQueries->getIdAndPriceOverrideLimitPercentageColumnName(),
            'cashierGroup.permissions',
            'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForStoreListAPI(),
            'counterUpdate.counter.location.city:' . $cityQueries->getBasicColumnNames(),
        );
    }

    public function loadDetailsForConfigurationAPI(Cashier $cashier): Cashier
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $currencyRateQueries = resolve(CurrencyRateQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return $cashier->load(
            'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForStoreConfiguration(),
            'counterUpdate.counter.location.company:' . $companyQueries->getBasicColumnNamesForStoreConfiguration(),
            'counterUpdate.counter.location.company.media:' . $mediaQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company.countries:' . $countryQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company.countries.currency:' . $currencyQueries->getBasicColumnNames(),
            'counterUpdate.counter.location.company.countries.currency.currencyRate:' . $currencyRateQueries->getBasicColumnNames(),
        );
    }

    public function updateLastLoginTime(Cashier $cashier): void
    {
        $cashier->last_login_at = Carbon::now()->format('Y-m-d H:i:s');
        $cashier->save();
    }

    public function generateToken(Cashier $cashier): string
    {
        $newAccessToken = $cashier->createToken('mobile-application');

        return $newAccessToken->plainTextToken;
    }

    public function loadLocationsAndGetWithBasicColumns(Cashier $cashier, ?string $afterUpdatedAt = null): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $cashier->load([
            'locations' => function ($query) use ($locationQueries, $afterUpdatedAt): void {
                $query->select(explode(',', $locationQueries->getBasicColumnNamesForStoreListAPI()))
                    ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                        $query->where('updated_at', '>=', $afterUpdatedAt);
                    });
            },
            'locations.city:' . $cityQueries->getBasicColumnNames(),
        ]);

        return $cashier->locations;
    }

    public function getCashierCompanyId(Cashier $cashier): int
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $cashier->load('employee:' . $employeeQueries->getColumnNamesForAdmin());

        /** @var Employee $employee */
        $employee = $cashier->getEmployee();

        return $employee->company_id;
    }

    public function isAuthorizedToSelectedLocation(Cashier $cashier, int $locationId, int $companyId): bool
    {
        return (bool) $cashier->locations()->where('id', $locationId)->where('company_id', $companyId)->exists();
    }

    public function changePin(Cashier $cashier, CashierChangePinData $cashierChangePinData): void
    {
        $cashier->pin = $cashierChangePinData->new_pin;
        $cashier->save();
    }

    public function setCounterUpdateId(Cashier $cashier, int $counterUpdateId): void
    {
        $cashier->counter_update_id = $counterUpdateId;
        $cashier->save();
    }

    public function unsetCounterUpdateId(Cashier $cashier): void
    {
        $cashier->counter_update_id = null;
        $cashier->save();
    }

    /**
     * @return mixed[]
     */
    public function getCashierLocationsId(Cashier $cashier): array
    {
        return $cashier->locations()->pluck('id')->toArray();
    }

    public function loadDetailsForCounterCloseApi(Cashier $cashier): Cashier
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return $cashier->load([
            'counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
            'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'employee:' . $employeeQueries->getFirstAndLastNameColumns(),
        ]);
    }

    public function getEmployeeIdColumnNames(): string
    {
        return 'id,employee_id';
    }

    public function getByCounterUpdateId(int $counterUpdateId): Cashier
    {
        return Cashier::query()
            ->select('id', 'counter_update_id')
            ->where('counter_update_id', $counterUpdateId)
            ->firstOrFail();
    }

    public function searchByName(string $searchText): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select(
            'id',
            'employee_id'
        )->whereHas('employee', $employeeQueries->searchByName($searchText));
    }

    public function getCashiersExport(array $filterData, int $companyId): Collection
    {
        $employeeQueries = new EmployeeQueries();
        $locationQueries = resolve(LocationQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);

        return Cashier::query()
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getBasicColumnNamesWithStatus(),
                'cashierGroup:' . $cashierGroupQueries->getBasicColumnNames(),
            ])
            ->select('id', 'employee_id', 'username', 'cashier_group_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($employeeQueries, $filterData): void {
                $query->where(function ($query) use ($employeeQueries, $filterData): void {
                    $query->where('username', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('employee', $employeeQueries->searchByBasicColumns($filterData['search_text']));
                });
            })
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getCashiersOfLocations(array $locationIds, int $companyId): Collection
    {
        $employeeQueries = new EmployeeQueries();
        $locationQueries = resolve(LocationQueries::class);

        return Cashier::query()
            ->select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getBasicColumnNamesWithStatus()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->whereHas('locations', $locationQueries->filterByIds($locationIds, LocationTypes::STORE->value))
            ->get();
    }

    public function getCashierListOfSelectedLocation(int $locationId, int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return Cashier::query()
            ->select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getBasicColumnNamesWithStatus()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->get();
    }

    public function doExistsByCashierUsername(string $userName, int $companyId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Cashier::query()->select('id', 'employee_id', 'username')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->where('username', $userName)
            ->exists();
    }

    public function doExistsByEmployeeId(?int $employeeId): bool
    {
        return Cashier::query()->select('id', 'employee_id')
            ->where('employee_id', $employeeId)
            ->exists();
    }

    public function findByCounterUpdateId(int $counterUpdateId): ?Cashier
    {
        return Cashier::query()
            ->select('id', 'counter_update_id')
            ->where('counter_update_id', $counterUpdateId)
            ->first();
    }

    public function getEmployeeWithRelation(): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function getCashiersForBulkUpdate(int $companyId): Collection
    {
        $employeeQueries = new EmployeeQueries();
        $locationQueries = resolve(LocationQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);

        return Cashier::select('id', 'username', 'cashier_group_id', 'employee_id')
        ->with([
            'locations:' . $locationQueries->getBasicColumnNames(),
            'employee:' . $employeeQueries->getBasicColumnNamesWithStatus(),
            'cashierGroup:' . $cashierGroupQueries->getBasicColumnNames(),
        ])
        ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
        ->orderBy('id', 'desc')
        ->get();
    }

    public function usernameTakenByAnotherCashier(string $username, string $mobileNumber, int $companyId): bool
    {
        return Cashier::whereCaseSensitive('username', $username)
            ->whereHas('employee', function ($query) use ($mobileNumber, $companyId): void {
                $query->select('id')
                    ->whereNotCaseSensitive('mobile_number', $mobileNumber)
                    ->where('company_id', $companyId);
            })
            ->whereCaseSensitive('username', $username)
            ->exists();
    }

    public function updateByMobileNumber(array $cashierData, string $mobileNumber, int $companyId): void
    {
        $locationIds = $cashierData['location_ids'];
        unset($cashierData['location_ids']);
        unset($cashierData['pin']);

        $cashier = Cashier::select('id')
            ->whereHas('employee', function ($query) use ($mobileNumber, $companyId): void {
                $query->select('id')
                    ->where('mobile_number', $mobileNumber)
                    ->where('company_id', $companyId);
            })
            ->first();

        if ($cashier instanceof Cashier) {
            $cashier->update($cashierData);
            $cashier->locations()->sync($locationIds);
        }
    }
}
