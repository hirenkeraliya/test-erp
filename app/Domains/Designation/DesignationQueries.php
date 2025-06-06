<?php

declare(strict_types=1);

namespace App\Domains\Designation;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Designation\DataObjects\DesignationData;
use App\Domains\Designation\DataObjects\SuperAdminDesignationData;
use App\Models\Admin;
use App\Models\Designation;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DesignationQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->designationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function listQueryForSuperAdmin(array $filterData): LengthAwarePaginator
    {
        $companyQueries = new CompanyQueries();

        return Designation::query()
            ->with(['company:' . $companyQueries->getBasicColumnNames()])
            ->whereHas('company', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->select('id', 'name', 'code', 'company_id')
            ->when($filterData['search_text'], function ($query) use ($filterData, $companyQueries): void {
                $query->where(function ($query) use ($filterData, $companyQueries): void {
                    $query
                    ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('company', $companyQueries->searchByName($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(DesignationData $designationData, int $companyId, Admin|StoreManager $user): void
    {
        $data = $designationData->all();
        $data['company_id'] = $companyId;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);
        $data['created_by_id'] = $user->id;
        Designation::create($data);
    }

    public function getById(int $designationId, int $companyId): Designation
    {
        return Designation::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->findOrFail($designationId);
    }

    public function update(DesignationData $designationData, int $designationId, int $companyId): void
    {
        $designation = $this->getById($designationId, $companyId);
        $designation->update($designationData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return Designation::where('company_id', $companyId)
            ->select('id', 'name', 'code')
            ->get();
    }

    public function getByIdWithoutCompanyFilter(int $designationId): Designation
    {
        return Designation::select('id', 'name', 'code', 'company_id')
            ->findOrFail($designationId);
    }

    public function updateForSuperAdmin(SuperAdminDesignationData $designationData, int $designationId): void
    {
        $designation = $this->getByIdWithoutCompanyFilter($designationId);
        $designation->update($designationData->all());
    }

    public function addForSuperAdmin(SuperAdminDesignationData $designationData, SuperAdmin $superAdmin): void
    {
        $data = $designationData->all();
        $data['created_by_type'] = ModelMapping::SUPER_ADMIN->name;
        $data['created_by_id'] = $superAdmin->id;
        Designation::create($data);
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return Designation::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function getDesignationsExport(array $filterData, int $companyId): Collection
    {
        return $this->designationQuery($filterData, $companyId)->get();
    }

    private function designationQuery(array $filterData, int $companyId): Builder
    {
        return Designation::query()
            ->select('id', 'name', 'code')
            ->where('company_id', $companyId)
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

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }
}
