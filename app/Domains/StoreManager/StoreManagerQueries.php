<?php

declare(strict_types=1);

namespace App\Domains\StoreManager;

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\DataObjects\ChangePasscodeData;
use App\Domains\StoreManager\DataObjects\ChangePasswordData;
use App\Domains\StoreManager\DataObjects\StoreManagerData;
use App\Models\Employee;
use App\Models\StoreManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StoreManagerQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getStoreManagersQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getAllByStoreCompanyId(int $companyId): Collection
    {
        return StoreManager::select('id')->with('locations')
            ->whereHas('locations', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId);
            })
            ->get();
    }

    public function getAllStoreManagerWithStore(int $locationId): Collection
    {
        return StoreManager::select('id')->with('locations')
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->where('id', $locationId);
            })
            ->get();
    }

    public function getAllStoreManagerWithStoreAndEmployee(int $locationId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::select('id', 'employee_id')
            ->with(['locations', 'employee:' . $employeeQueries->getColumnNamesForPromoter()])
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->where('id', $locationId);
            })
            ->get();
    }

    public function getAllStoreManagerWithStores(array $locationIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return StoreManager::select('id')->with('locations:' . $locationQueries->getNameColumnName())
            ->whereHas('locations', function ($query) use ($locationIds): void {
                $query->whereIntegerInRaw('id', $locationIds);
            })
            ->get();
    }

    public function getAllStoreManagerWithLocations(array $locationIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        return StoreManager::select('id')->with('locations:' . $locationQueries->getNameColumnName())
            ->whereHas('locations', function ($query) use ($locationIds): void {
                $query->whereIntegerInRaw('id', $locationIds)
                    ->where('type_id', LocationTypes::STORE->value);
            })
            ->get();
    }

    public function addNew(StoreManagerData $storeManagerData): void
    {
        $storeManagerValidationData = $storeManagerData->all();
        unset($storeManagerValidationData['location_ids']);
        unset($storeManagerValidationData['brand_ids']);
        unset($storeManagerValidationData['role_ids']);

        $storeManagerValidationData['password'] = bcrypt($storeManagerValidationData['password']);

        $storeManager = StoreManager::create($storeManagerValidationData);
        $storeManager->locations()->sync($storeManagerData->location_ids);

        $storeManager->syncRoles($storeManagerData->role_ids);

        if ($storeManagerData->brand_ids) {
            $storeManager->brands()->sync($storeManagerData->brand_ids);
        }
    }

    public function getByIdWithStores(int $storeManagerId, int $companyId): StoreManager
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $roleQueries = resolve(RoleQueries::class);

        return StoreManager::select(
            'id',
            'username',
            'passcode',
            'employee_id',
            'can_manage_wholesale',
            'price_override_limit_percentage_for_item',
            'price_override_limit_percentage_for_cart',
            'price_override_type'
        )
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'employee.media:' . $mediaQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'roles:' . $roleQueries->getBasicColumns(),
            )
            ->findOrFail($storeManagerId);
    }

    public function getById(int $storeManagerId, int $companyId): StoreManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::select(
            'id',
            'username',
            'employee_id',
            'password',
            'passcode',
            'price_override_limit_percentage_for_item',
            'price_override_limit_percentage_for_cart',
            'price_override_type',
            'can_manage_wholesale'
        )
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($storeManagerId);
    }

    public function updateFcmToken(string $fcmToken, int $storeManagerId, int $companyId): void
    {
        $storeManager = $this->getById($storeManagerId, $companyId);
        $storeManager->fcm_token = $fcmToken;
        $storeManager->save();
    }

    public function getByIds(array $ids, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::select('id', 'passcode', 'employee_id')
            ->whereIntegerInRaw('id', $ids)
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(['employee:' . $employeeQueries->getNamesAndStatusColumns()])
            ->get();
    }

    public function getByIdWithEmployee(int $storeManagerId, int $companyId): ?StoreManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::select('id', 'passcode', 'employee_id')
            ->where('id', $storeManagerId)
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(['employee:' . $employeeQueries->getNamesAndStatusColumns()])
            ->first();
    }

    public function getAllStoreManagerByCompany(int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::query()
            ->withWhereHas('employee', function ($query) use ($companyId, $employeeQueries): void {
                $query->select(explode(',', $employeeQueries->getFirstAndLastNameColumns()))
                    ->where($employeeQueries->filterByCompany($companyId));
            })
            ->get();
    }

    public function loadEmployee(StoreManager $storeManager): StoreManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return $storeManager->load('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function update(StoreManagerData $storeManagerData, int $storeManagerId, int $companyId): void
    {
        $storeManager = $this->getById($storeManagerId, $companyId);

        $storeManagerValidatedData = $storeManagerData->all();

        unset(
            $storeManagerValidatedData['location_ids'],
            $storeManagerValidatedData['password'],
            $storeManagerValidatedData['passcode'],
            $storeManagerValidatedData['brand_ids'],
            $storeManagerValidatedData['role_ids'],
        );

        $storeManager->update($storeManagerValidatedData);

        $storeManager->syncRoles($storeManagerData->role_ids);
        $storeManager->locations()->sync($storeManagerData->location_ids);
        $storeManager->brands()->sync((array) $storeManagerData->brand_ids);
    }

    public function changePassword(StoreManager $storeManager, ChangePasswordData $changePasswordData): void
    {
        $storeManager->password = bcrypt($changePasswordData->new_password);
        $storeManager->save();
    }

    public function changePasscode(StoreManager $storeManager, ChangePasscodeData $changePasscodeData): void
    {
        $storeManager->passcode = $changePasscodeData->new_passcode;
        $storeManager->save();
    }

    public function fetchStoreManagerByUsername(string $username): ?StoreManager
    {
        $employeeQueries = new EmployeeQueries();
        $storeManager = StoreManager::select(
            'id',
            'username',
            'employee_id',
            'forgot_password_token',
            'forgot_password_token_expiration_at'
        )->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->where('username', $username)
            ->first();

        if (null !== $storeManager) {
            /** @var Employee $employee */
            $employee = $storeManager->employee;
            $storeManager->forgot_password_token = md5($employee->email . now());
            $storeManager->forgot_password_token_expiration_at = now()->addHour()->format('Y-m-d H:i:s');
            $storeManager->save();
        }

        return $storeManager;
    }

    public function getByToken(string $token): StoreManager
    {
        return StoreManager::where('forgot_password_token', $token)
            ->where('forgot_password_token_expiration_at', '>=', now())
            ->firstOrFail();
    }

    public function resetPassword(StoreManager $storeManager, string $password): void
    {
        $storeManager->password = bcrypt($password);
        $storeManager->forgot_password_token = null;
        $storeManager->forgot_password_token_expiration_at = null;
        $storeManager->save();
    }

    public function getStoreManagerCompanyId(StoreManager $storeManager): int
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $storeManager->load('employee:' . $employeeQueries->getColumnNamesForAdmin());

        /** @var Employee $employee */
        $employee = $storeManager->employee;

        return $employee->company_id;
    }

    public function getStoreManagerListForPos(int $locationId, ?string $afterUpdatedAt = null): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return StoreManager::whereHas(
            'locations',
            $locationQueries->filterById($locationId, LocationTypes::STORE->value)
        )
            ->with([
                'employee:' . $employeeQueries->getBasicColumnNames(),
                'locations:' . $locationQueries->getBasicColumnNames(),
                'brands:' . $brandQueries->getBasicColumnNames(),
            ])
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    /**
     * @return mixed[]
     */
    public function getStoreManagerStoresId(StoreManager $storeManager): array
    {
        return $storeManager->locations()->pluck('id')->toArray();
    }

    public function getStoreManagerStores(StoreManager $storeManager): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        $storeManager->load('locations:' . $locationQueries->getBasicColumnNames());

        return $storeManager->locations;
    }

    public function getStoreManagerByUsername(string $username): ?StoreManager
    {
        $employeeQueries = new EmployeeQueries();
        $companyQueries = new CompanyQueries();

        return StoreManager::select('id', 'employee_id', 'password')
            ->with('employee:' . $employeeQueries->getBasicColumnNamesWithStatus())
            ->with(['employee.company:' . $companyQueries->getBasicColumnNames()])
            ->where('username', $username)
            ->first();
    }

    public function existsByIdStoreIdAndStatus(int $storeManagerId, int $locationId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return StoreManager::select('id')
            ->where('id', $storeManagerId)
            ->whereHas('employee', $employeeQueries->filterByStatus())
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->exists();
    }

    public function existsByIdStoreIdAndPasscode(int $locationId, CreditNoteRefundData $creditNoteRefundData): bool
    {
        $locationQueries = resolve(LocationQueries::class);

        return StoreManager::query()
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->whereCaseSensitive('passcode', $creditNoteRefundData->passcode)
            ->where('id', $creditNoteRefundData->store_manager_id)
            ->exists();
    }

    public function getEmployeeIdColumnNames(): string
    {
        return 'id,employee_id';
    }

    public function getIdColumnName(): string
    {
        return 'id';
    }

    public function getByStoreIdWithEmployee(int $locationId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return StoreManager::query()
            ->select('id', 'employee_id')
            ->with([
                'employee:' . $employeeQueries->getBasicColumnNames(),
                'employee.company:' . $companyQueries->getBasicColumnNames(),
                'employee.company.media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->get();
    }

    public function getByStoreIdsWithEmployee(array $locationIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::query()
            ->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->whereHas('locations', $locationQueries->filterByIds($locationIds, LocationTypes::STORE->value))
            ->get();
    }

    public function getByLocationIdsWithEmployee(array $locationIds): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::query()
            ->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->whereHas('locations', $locationQueries->filterByIds($locationIds, LocationTypes::STORE->value))
            ->get();
    }

    public function searchByEmployeeName(string $searchText): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->where(function ($query) use ($searchText, $employeeQueries): void {
            $query->whereHas('employee', $employeeQueries->searchByFirstAndLastName($searchText));
        });
    }

    public function filterById(int $storeManagerId): Closure
    {
        return fn ($query) => $query->where('id', $storeManagerId);
    }

    public function getStoreManagersExport(array $filterData, int $companyId): Collection
    {
        return $this->getStoreManagersQuery($filterData, $companyId)->get();
    }

    public function updateUsername(StoreManager $storeManager, string $username): void
    {
        $storeManager->username = $username;
        $storeManager->save();
    }

    public function doExistsByStoreManagerUsername(string $userName, int $companyId): bool
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::query()->select('id', 'employee_id', 'username')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->where('username', $userName)
            ->exists();
    }

    public function doExistsByEmployeeId(?int $employeeId): bool
    {
        return StoreManager::query()->select('id', 'employee_id')
            ->where('employee_id', $employeeId)
            ->exists();
    }

    public function loadStoresWithSearch(StoreManager $storeManager, array $filterData): StoreManager
    {
        return $storeManager->load([
            'locations' => function ($query) use ($filterData): void {
                $query->select('id', 'name', 'code')
                ->when(null !== $filterData['search_text'], function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            },
        ]);
    }

    public function loadEmployeeAndStores(StoreManager $storeManager): StoreManager
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return $storeManager->load(
            'locations:' . $locationQueries->getBasicColumnNames(),
            'employee:' . $employeeQueries->getBasicColumnNamesWithStatus(),
        );
    }

    public function existsByIdAndStoreId(int $storeManagerId, int $locationId): bool
    {
        $locationQueries = resolve(LocationQueries::class);

        return StoreManager::select('id')
            ->where('id', $storeManagerId)
            ->whereHas('locations', $locationQueries->filterById($locationId, LocationTypes::STORE->value))
            ->exists();
    }

    public function findByIdAndCompanyId(int $storeManagerId, int $companyId): ?StoreManager
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::select('id', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->find($storeManagerId);
    }

    public function getEmployeeWithRelation(): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function getAllStoreManagerByStoreIdAndCompanyId(int $locationId, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::select('id')
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->where('id', $locationId);
            })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->get();
    }

    private function getStoreManagersQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return StoreManager::query()
            ->select(
                'id',
                'employee_id',
                'price_override_limit_percentage_for_item',
                'price_override_type',
                'price_override_limit_percentage_for_cart'
            )
            ->with([
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getBasicColumnNamesWithStatus(),
            ])
        ->when($filterData['search_text'], function ($query) use (
            $filterData,
            $employeeQueries,
            $locationQueries
        ): void {
            $query->where(function ($query) use ($filterData, $employeeQueries, $locationQueries): void {
                $query->where(
                    'price_override_limit_percentage_for_item',
                    'like',
                    '%' . $filterData['search_text'] . '%'
                )
                        ->orWhereHas('employee', $employeeQueries->searchByBasicColumns($filterData['search_text']))
                        ->orWhereHas('locations', $locationQueries->searchByName($filterData['search_text']));
            });
        })
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
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

    public function getTokenById(int $storeManagerId): ?StoreManager
    {
        return StoreManager::query()
            ->select('id', 'fcm_token')
            ->whereNotNull('fcm_token')
            ->find($storeManagerId);
    }

    public function getStoreManagersForBulkUpdate(int $companyId): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $roleQueries = resolve(RoleQueries::class);

        return StoreManager::select(
            'id',
            'employee_id',
            'username',
            'price_override_type',
            'price_override_limit_percentage_for_item',
            'price_override_limit_percentage_for_cart',
            'can_manage_wholesale',
        )
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(
                'locations:' . $locationQueries->getBasicColumnNames(),
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'brands:' . $brandQueries->getBasicColumnNames(),
                'roles:' . $roleQueries->getBasicColumns(),
            )
            ->orderBy('id', 'desc')
            ->get();
    }

    public function usernameTakenByAnotherStoreManager(string $username, string $firstName, int $companyId): bool
    {
        return StoreManager::select('id', 'employee_id')
            ->whereHas('employee', function ($query) use ($firstName, $companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->whereNotCaseSensitive('first_name', $firstName);
            })
            ->whereCaseSensitive('username', $username)
            ->exists();
    }

    public function updateByMobileNumber(array $storeManagerData, string $mobileNumber, int $companyId): void
    {
        $storeManager = StoreManager::select('id', 'employee_id')
            ->whereHas('employee', function ($query) use ($mobileNumber, $companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->where('mobile_number', $mobileNumber);
            })
            ->first();

        $storeManagerValidatedData = $storeManagerData;

        unset(
            $storeManagerData['location_ids'],
            $storeManagerData['password'],
            $storeManagerData['passcode'],
            $storeManagerData['brand_ids'],
            $storeManagerData['role_ids'],
        );
        if ($storeManager instanceof StoreManager) {
            $storeManager->update($storeManagerData);
            $storeManager->syncRoles($storeManagerValidatedData['role_ids']);
            $storeManager->locations()->sync($storeManagerValidatedData['location_ids']);
            $storeManager->brands()->sync((array) $storeManagerValidatedData['brand_ids']);
        }
    }

    public function getByStoreManagerCompanyId(int $storeManagerId, int $companyId): StoreManager
    {
        $employeeQueries = new EmployeeQueries();

        return StoreManager::query()
            ->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getBasicColumnNamesWithStatus())
            ->findOrFail($storeManagerId);
    }

    public function createToken(StoreManager $storeManager): string
    {
        return $storeManager->createToken('store_manager_app', ['store_manager_scope'])->plainTextToken;
    }

    public function getStoreManagerData(int $storeManagerId): StoreManager
    {
        return StoreManager::select([
            'id', 'employee_id', 'username', 'two_factor_secret', 'two_factor_recovery_codes']
        )->where('id', $storeManagerId)->firstOrFail();
    }

    public function updateStoreManagerProfile(int $storeManagerId, array $storeManagerData): void
    {
        $storeManager = $this->getStoreManagerData($storeManagerId);
        $storeManager->update($storeManagerData);
    }
}
