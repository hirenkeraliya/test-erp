<?php

declare(strict_types=1);

namespace App\Domains\SizeGroup;

use App\Domains\SizeGroup\DataObjects\SizeGroupData;
use App\Models\SizeGroup;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class SizeGroupQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getSizeGroups($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(SizeGroupData $sizeGroupData, int $companyId): void
    {
        $data = $sizeGroupData->all();
        $data['company_id'] = $companyId;

        SizeGroup::create($data);
    }

    public function getById(int $sizeGroupId, int $companyId): SizeGroup
    {
        return SizeGroup::select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->findOrFail($sizeGroupId);
    }

    public function update(SizeGroupData $sizeGroupData, int $sizeGroupId, int $companyId): void
    {
        $color = $this->getById($sizeGroupId, $companyId);
        $color->update($sizeGroupData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getSizeGroupsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getSizeGroups($filterData, $companyId)->get();
    }

    public function getSizeGroupByCompanyId(int $companyId): SupportCollection
    {
        return SizeGroup::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return SizeGroup::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function existsByCode(string $code, int $companyId): bool
    {
        return SizeGroup::whereCaseSensitive('code', $code)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $sizeGroupName, int $companyId): ?SizeGroup
    {
        return SizeGroup::select('id', 'name')
            ->whereCaseSensitive('name', $sizeGroupName)
            ->where('company_id', $companyId)
            ->first();
    }

    public function codeTakenByAnotherSizeGroup(string $code, string $name, int $companyId): bool
    {
        return SizeGroup::whereNotCaseSensitive('name', $name)
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function updateByName(array $sizeGroupData, string $name, int $companyId): void
    {
        $sizeGroup = SizeGroup::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first();

        if ($sizeGroup instanceof SizeGroup) {
            $sizeGroup->update($sizeGroupData);
        }
    }

    private function getSizeGroups(array $filterData, int $companyId): Builder
    {
        return SizeGroup::query()
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
}
