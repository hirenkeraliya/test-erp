<?php

declare(strict_types=1);

namespace App\Domains\WarehouseManager;

use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\WarehouseManager\DataObjects\ChangePasswordData;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerData;
use App\Models\Employee;
use App\Models\WarehouseManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WarehouseManagerQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getWarehouseManagersQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getAllByWarehouseCompanyId(int $companyId): Collection
    {
        return WarehouseManager::select('id')->with('locations')
            ->whereHas('locations', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId);
            })
            ->get();
    }

    public function getAllWarehouseManagerWithWarehouse(int $locationId): Collection
    {
        return WarehouseManager::select('id')->with('locations')
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->where('id', $locationId);
            })
            ->get();
    }

    public function addNew(WarehouseManagerData $warehouseManagerData): void
    {
        $warehouseManagerValidationData = $warehouseManagerData->all();
        unset($warehouseManagerValidationData['location_ids']);
        unset($warehouseManagerValidationData['role_ids']);

        $warehouseManagerValidationData['password'] = bcrypt($warehouseManagerValidationData['password']);

        $warehouseManager = WarehouseManager::create($warehouseManagerValidationData);
        $warehouseManager->locations()->sync($warehouseManagerData->location_ids);
        $warehouseManager->syncRoles($warehouseManagerData->role_ids);
    }

    public function getByIdWithWarehouses(int $warehouseManagerId, int $companyId): WarehouseManager
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $roleQueries = resolve(RoleQueries::class);

        return WarehouseManager::select('id', 'username', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with([
                'locations:' . $locationQueries->getBasicColumnNamesOfWarehouse(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'employee.media:' . $mediaQueries->getBasicColumnNames(),
                'roles:' . $roleQueries->getBasicColumns(),
            ])
            ->findOrFail($warehouseManagerId);
    }

    public function getById(int $warehouseManagerId, int $companyId): WarehouseManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return WarehouseManager::select('id', 'username', 'employee_id', 'password')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($warehouseManagerId);
    }

    public function updateFcmToken(string $fcmToken, int $warehouseManagerId, int $companyId): void
    {
        $warehouseManager = $this->getById($warehouseManagerId, $companyId);
        $warehouseManager->fcm_token = $fcmToken;
        $warehouseManager->save();
    }

    public function update(WarehouseManagerData $warehouseManagerData, int $warehouseManagerId, int $companyId): void
    {
        $warehouseManager = $this->getById($warehouseManagerId, $companyId);

        $warehouseManagerValidatedData = $warehouseManagerData->all();

        unset(
            $warehouseManagerValidatedData['location_ids'],
            $warehouseManagerValidatedData['password'],
            $warehouseManagerValidatedData['passcode'],
            $warehouseManagerValidatedData['role_ids'],
        );

        $warehouseManager->update($warehouseManagerValidatedData);

        $warehouseManager->locations()->sync($warehouseManagerData->location_ids);
        $warehouseManager->syncRoles($warehouseManagerData->role_ids);
    }

    public function changePassword(WarehouseManager $warehouseManager, ChangePasswordData $changePasswordData): void
    {
        $warehouseManager->password = bcrypt($changePasswordData->new_password);
        $warehouseManager->save();
    }

    public function fetchWarehouseManagerByUsername(string $username): ?WarehouseManager
    {
        $employeeQueries = new EmployeeQueries();
        $warehouseManager = WarehouseManager::select(
            'id',
            'username',
            'employee_id',
            'forgot_password_token',
            'forgot_password_token_expiration_at'
        )->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->where('username', $username)
            ->first();

        if (null !== $warehouseManager) {
            /** @var Employee $employee */
            $employee = $warehouseManager->employee;
            $warehouseManager->forgot_password_token = md5($employee->email . now());
            $warehouseManager->forgot_password_token_expiration_at = now()->addHour()->format('Y-m-d H:i:s');
            $warehouseManager->save();
        }

        return $warehouseManager;
    }

    public function getByToken(string $token): WarehouseManager
    {
        return WarehouseManager::where('forgot_password_token', $token)
            ->where('forgot_password_token_expiration_at', '>=', now())
            ->firstOrFail();
    }

    public function resetPassword(WarehouseManager $warehouseManager, string $password): void
    {
        $warehouseManager->password = bcrypt($password);
        $warehouseManager->forgot_password_token = null;
        $warehouseManager->forgot_password_token_expiration_at = null;
        $warehouseManager->save();
    }

    public function getWarehouseManagersExport(array $filterData, int $companyId): Collection
    {
        return $this->getWarehouseManagersQuery($filterData, $companyId)->get();
    }

    public function getwarehouseManagerWarehouses(WarehouseManager $warehouseManager): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        $warehouseManager->load('locations:' . $locationQueries->getBasicColumnNamesOfWarehouse());

        return $warehouseManager->locations;
    }

    /**
     * @return mixed[]
     */
    public function getWarehouseManagerWarehouseIds(WarehouseManager $warehouseManager): array
    {
        return $warehouseManager->locations()->pluck('id')->toArray();
    }

    public function getIdColumnName(): string
    {
        return 'id';
    }

    public function getEmployeeIdColumnNames(): string
    {
        return 'id,employee_id';
    }

    public function updateUsername(WarehouseManager $warehouseManager, string $username): void
    {
        $warehouseManager->username = $username;
        $warehouseManager->save();
    }

    public function loadWarehouses(WarehouseManager $warehouseManager, array $filterData): WarehouseManager
    {
        return $warehouseManager->load([
            'locations' => function ($query) use ($filterData): void {
                $query->select('id', 'name', 'code')
                ->when(null !== $filterData['search_text'], function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            },
        ]);
    }

    public function existsByIdAndWarehouseId(int $warehouseManagerId, int $locationId): bool
    {
        $locationQueries = resolve(LocationQueries::class);

        return WarehouseManager::select('id')
            ->where('id', $warehouseManagerId)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::WAREHOUSE->value))
            ->exists();
    }

    public function getEmployeeWithRelation(): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    private function getWarehouseManagersQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return WarehouseManager::query()
            ->select('id', 'employee_id')
            ->with([
                'locations:' . $locationQueries->getBasicColumnNamesOfWarehouse(),
                'employee:' . $employeeQueries->getBasicColumnNamesWithStatus(),
            ])
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $employeeQueries,
                $locationQueries
            ): void {
                $query->where(function ($query) use ($filterData, $employeeQueries, $locationQueries): void {
                    $query->whereHas('employee', $employeeQueries->searchByBasicColumns($filterData['search_text']))
                        ->orWhereHas('locations', $locationQueries->searchByName($filterData['search_text']));
                });
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::WAREHOUSE->value)
                );
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getTokenById(int $warehouseManagerId): ?WarehouseManager
    {
        return WarehouseManager::query()
            ->select('id', 'fcm_token')
            ->whereNotNull('fcm_token')
            ->find($warehouseManagerId);
    }

    public function loadEmployee(WarehouseManager $warehouseManager): WarehouseManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return $warehouseManager->load('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function updateExternalLoginToken(int $warehouseManagerId, int $companyId, string $token): void
    {
        $warehouseManager = $this->getById($warehouseManagerId, $companyId);
        $warehouseManager->external_login_token = $token;
        $warehouseManager->save();
    }

    public function getByStaffIdAndCompanyId(string $staffId, int $companyId): ?WarehouseManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return WarehouseManager::select('id', 'username', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompanyAndStaffId($staffId, $companyId))
            ->first();
    }

    public function getByIdAndExternalLoginToken(int $id, string $token): WarehouseManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return WarehouseManager::select('id', 'username', 'employee_id')
            ->with(['employee:' . $employeeQueries->getNameAndStaffIdColumns()])
            ->where('id', $id)
            ->where('external_login_token', $token)
            ->firstOrFail();
    }

    public function getByWarehouseManagerCompanyId(int $warehouseManagerId, int $companyId): WarehouseManager
    {
        $employeeQueries = new EmployeeQueries();

        return WarehouseManager::query()
            ->select('id', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($warehouseManagerId);
    }

    public function getWarehouseManagerByUsername(string $username): ?WarehouseManager
    {
        $employeeQueries = new EmployeeQueries();
        $companyQueries = new CompanyQueries();

        return WarehouseManager::query()
            ->select('id', 'employee_id', 'password')
            ->with(['employee:' . $employeeQueries->getBasicColumnNamesWithStatus()])
            ->with(['employee.company:' . $companyQueries->getBasicColumnNames()])
            ->where('username', $username)
            ->first();
    }

    public function createToken(WarehouseManager $warehouseManager): string
    {
        return $warehouseManager->createToken('warehouse_manager_app', ['warehouse_manager_scope'])->plainTextToken;
    }

    public function getWarehouseManagerData(int $warehouseManagerId): WarehouseManager
    {
        return WarehouseManager::select([
            'id', 'employee_id', 'username', 'two_factor_secret', 'two_factor_recovery_codes']
        )->where('id', $warehouseManagerId)->firstOrFail();
    }

    public function updateWarehouseManagerProfile(int $warehouseManagerId, array $warehouseManagerData): void
    {
        $warehouseManager = $this->getWarehouseManagerData($warehouseManagerId);
        $warehouseManager->update($warehouseManagerData);
    }
}
