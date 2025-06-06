<?php

declare(strict_types=1);

namespace App\Domains\PromoterGroup;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\PromoterGroup\DataObjects\PromoterGroupData;
use App\Models\Admin;
use App\Models\PromoterGroup;
use App\Models\StoreManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class PromoterGroupQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getPromoterGroups($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getByIds(array $promoterGroupIds): SupportCollection
    {
        return PromoterGroup::select('name')
            ->whereIntegerInRaw('id', $promoterGroupIds)
            ->get();
    }

    public function addNew(PromoterGroupData $promoterGroupData, int $companyId, Admin|StoreManager $user): void
    {
        $data = $promoterGroupData->all();
        $data['company_id'] = $companyId;
        $data['created_by_type'] = ModelMapping::getCaseName($user::class);
        $data['created_by_id'] = $user->id;

        PromoterGroup::create($data);
    }

    public function getById(int $promoterGroupId, int $companyId): PromoterGroup
    {
        return PromoterGroup::select('id', 'name', 'code', 'type_id')
            ->where('company_id', $companyId)
            ->findOrFail($promoterGroupId);
    }

    public function getByName(string $name, int $companyId): ?PromoterGroup
    {
        return PromoterGroup::select('id', 'name')
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->first();
    }

    public function filterByIds(array $promoterGroupId): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('id', $promoterGroupId);
    }

    public function update(PromoterGroupData $promoterGroupData, int $promoterGroupId, int $companyId): void
    {
        $color = $this->getById($promoterGroupId, $companyId);
        $color->update($promoterGroupData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getPromoterGroupsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getPromoterGroups($filterData, $companyId)->get();
    }

    public function getPromoterGroupByCompanyId(int $companyId): Collection
    {
        return PromoterGroup::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function doExistsByName(string $name, int $companyId): bool
    {
        return PromoterGroup::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->exists();
    }

    private function getPromoterGroups(array $filterData, int $companyId): Builder
    {
        return PromoterGroup::query()
            ->select('id', 'name', 'code', 'type_id')
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

    public function getPromoterGroupNameForFilter(array $promoterGroupIds): string
    {
        $promoterData = [];
        $promoterGroup = PromoterGroup::select('name')
            ->whereIntegerInRaw('id', values: $promoterGroupIds)
            ->get();

        if ($promoterGroup->isNotEmpty()) {
            $promoterData = $promoterGroup->pluck('name')->toArray();
        }

        return implode(', ', $promoterData);
    }
}
