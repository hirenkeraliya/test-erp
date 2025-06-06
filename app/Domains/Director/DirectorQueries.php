<?php

declare(strict_types=1);

namespace App\Domains\Director;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Director\DataObjects\ChangePasscodeData;
use App\Domains\Director\DataObjects\DirectorData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Models\Admin;
use App\Models\Director;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DirectorQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getDirectorsQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(DirectorData $directorData, Admin|StoreManager $user): void
    {
        $directorValidationData = $directorData->all();
        unset($directorValidationData['location_ids']);
        $directorValidationData['created_by_type'] = ModelMapping::getCaseName($user::class);
        $directorValidationData['created_by_id'] = $user->id;

        $director = Director::create($directorValidationData);
        $director->locations()->sync($directorData->location_ids);
    }

    public function getByIdWithEmployeeAndLocations(int $directorId, int $companyId): Director
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return Director::select(
            'id',
            'employee_id',
            'passcode',
            'price_override_type',
            'price_override_limit_percentage_for_item',
            'price_override_limit_percentage_for_cart'
        )
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getNamesAndStatusColumns(),
            ])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($directorId);
    }

    public function getById(int $directorId, int $companyId): Director
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Director::whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($directorId);
    }

    public function getByIds(array $ids, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Director::select('id', 'passcode', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(['employee:' . $employeeQueries->getNamesAndStatusColumns()])
            ->whereIntegerInRaw('id', $ids)
            ->get();
    }

    public function update(DirectorData $directorData, int $directorId, int $companyId): void
    {
        $directorValidationData = $directorData->all();
        unset(
            $directorValidationData['location_ids'],
            $directorValidationData['passcode']
        );

        $director = $this->getById($directorId, $companyId);
        $director->update($directorValidationData);
        $director->locations()->sync($directorData->location_ids);
    }

    public function getList(int $locationId, int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Director::select(
            'id',
            'employee_id',
            'passcode',
            'price_override_type',
            'price_override_limit_percentage_for_item',
            'price_override_limit_percentage_for_cart'
        )
            ->with(['employee:' . $employeeQueries->getBasicColumnNames()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function changePasscode(Director $director, ChangePasscodeData $changePasscodeData): void
    {
        $director->passcode = $changePasscodeData->new_passcode;
        $director->save();
    }

    public function existsByIdLocationIdAndStatus(int $directorId, int $companyId, int $locationId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return Director::select('id')
            ->where('id', $directorId)
            ->whereHas('employee', $employeeQueries->filterByActiveAndCompanyId($companyId))
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->exists();
    }

    public function getDirectorsExport(array $filterData, int $companyId): Collection
    {
        return $this->getDirectorsQuery($filterData, $companyId)->get();
    }

    public function findByIdAndCompanyId(int $directorId, int $companyId): ?Director
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Director::select('id', 'employee_id')
                ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
                ->find($directorId);
    }

    public function getByIdWithEmployee(int $directorId, int $companyId): ?Director
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Director::select('id', 'passcode', 'employee_id')
        ->where('id', $directorId)
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(['employee:' . $employeeQueries->getNamesAndStatusColumns()])
            ->first();
    }

    private function getDirectorsQuery(array $filterData, int $companyId): Builder
    {
        $employeeQueries = new EmployeeQueries();
        $locationQueries = new LocationQueries();

        return Director::query()
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getBasicColumnNamesWithStatus(),
            ])
            ->select(
                'id',
                'employee_id',
                'price_override_limit_percentage_for_item',
                'price_override_type',
                'price_override_limit_percentage_for_cart'
            )
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $employeeQueries,
                $locationQueries
            ): void {
                $query->where(function ($query) use ($filterData, $employeeQueries, $locationQueries): void {
                    $query->whereHas(
                        'employee',
                        $employeeQueries->searchByBasicColumns($filterData['search_text'])
                    )->orWhereHas('locations', $locationQueries->searchStoreByName($filterData['search_text']));
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
            });
    }
}
