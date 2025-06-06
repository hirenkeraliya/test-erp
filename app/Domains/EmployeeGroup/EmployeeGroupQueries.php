<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\EmployeeGroup\DataObjects\EmployeeGroupData;
use App\Domains\EmployeeGroup\DataObjects\SuperAdminEmployeeGroupData;
use App\Models\Admin;
use App\Models\EmployeeGroup;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployeeGroupQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->employeeGroupQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function listQueryForSuperAdmin(array $filterData): LengthAwarePaginator
    {
        return $this->employeeGroupQueryForSuperAdmin($filterData)->paginate($filterData['per_page']);
    }

    public function addNew(EmployeeGroupData $employeeGroupData, int $companyId, Admin|StoreManager $user): void
    {
        $employeeGroupRecord = $employeeGroupData->all();
        $employeeGroupRecord['company_id'] = $companyId;
        $employeeGroupRecord['created_by_type'] = ModelMapping::getCaseName($user::class);
        $employeeGroupRecord['created_by_id'] = $user->id;

        EmployeeGroup::create($employeeGroupRecord);
    }

    public function addForSuperAdmin(SuperAdminEmployeeGroupData $employeeGroupData, SuperAdmin $superAdmin): void
    {
        $data = $employeeGroupData->all();
        $data['created_by_type'] = ModelMapping::SUPER_ADMIN->name;
        $data['created_by_id'] = $superAdmin->id;

        EmployeeGroup::create($data);
    }

    public function getById(int $employeeGroupId, int $companyId): EmployeeGroup
    {
        return EmployeeGroup::select(
            'id',
            'name',
            'code',
            'item_purchase_limit',
            'purchase_limit_type_id',
            'limit_reset_type_id',
            'limit_reset'
        )
            ->where('company_id', $companyId)
            ->findOrFail($employeeGroupId);
    }

    public function getByIdWithoutCompanyFilter(int $employeeGroupId): EmployeeGroup
    {
        return EmployeeGroup::select(
            'id',
            'company_id',
            'name',
            'code',
            'item_purchase_limit',
            'purchase_limit_type_id',
            'limit_reset_type_id',
            'limit_reset'
        )
            ->findOrFail($employeeGroupId);
    }

    public function updateForSuperAdmin(SuperAdminEmployeeGroupData $employeeGroupData, int $employeeGroupId): void
    {
        $employeeGroup = $this->getByIdWithoutCompanyFilter($employeeGroupId);
        $employeeGroup->update($employeeGroupData->all());
    }

    public function update(EmployeeGroupData $employeeGroupData, int $employeeGroupId, int $companyId): void
    {
        $employeeGroup = $this->getById($employeeGroupId, $companyId);

        $employeeGroup->update($employeeGroupData->all());
    }

    public function getEmployeeGroupsExport(array $filterData, int $companyId): Collection
    {
        return $this->employeeGroupQuery($filterData, $companyId)->get();
    }

    public function getSuperAdminEmployeeGroupsExport(array $filterData): Collection
    {
        return $this->employeeGroupQueryForSuperAdmin($filterData)->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return EmployeeGroup::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,company_id,name,code,item_purchase_limit,purchase_limit_type_id,limit_reset_type_id,limit_reset';
    }

    public function getIdByName(string $name, int $companyId): ?int
    {
        return EmployeeGroup::query()->select('id', 'company_id', 'name')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first()
            ?->id;
    }

    public function employeeGroupExists(string $name, int $companyId): bool
    {
        return EmployeeGroup::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    private function employeeGroupQuery(array $filterData, int $companyId): Builder
    {
        return EmployeeGroup::query()
            ->select(
                'id',
                'name',
                'code',
                'item_purchase_limit',
                'purchase_limit_type_id',
                'limit_reset_type_id',
                'limit_reset'
            )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when(
                array_key_exists('after_updated_at', $filterData) && $filterData['after_updated_at'],
                function ($query) use ($filterData): void {
                    $query->where('updated_at', '>=', $filterData['after_updated_at']);
                }
            )
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function employeeGroupQueryForSuperAdmin(array $filterData): Builder
    {
        $companyQueries = new CompanyQueries();

        return EmployeeGroup::query()
            ->select(
                'id',
                'company_id',
                'name',
                'code',
                'item_purchase_limit',
                'item_purchase_limit',
                'purchase_limit_type_id',
                'limit_reset_type_id',
                'limit_reset'
            )
            ->with(['company:' . $companyQueries->getBasicColumnNames()])
            ->whereHas('company', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
