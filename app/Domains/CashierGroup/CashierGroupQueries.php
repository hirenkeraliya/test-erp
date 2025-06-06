<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup;

use App\Domains\CashierGroup\DataObjects\CashierGroupData;
use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\CashierGroup;
use App\Models\StoreManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashierGroupQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->cashierGroupQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(CashierGroupData $cashierGroupData, int $companyId, Admin|StoreManager $user): void
    {
        $cashierGroupRecord = $cashierGroupData->all();
        $cashierGroupRecord['company_id'] = $companyId;
        $cashierGroupRecord['created_by_type'] = ModelMapping::getCaseName($user::class);
        $cashierGroupRecord['created_by_id'] = $user->id;

        unset($cashierGroupRecord['permission_ids']);
        $cashierGroup = CashierGroup::create($cashierGroupRecord);

        $permissionIds = collect($cashierGroupData->permission_ids)->map(fn ($permissionId): array => [
            'permission_id' => $permissionId,
        ])->toArray();

        $cashierGroup->permissions()->createMany($permissionIds);
    }

    public function getByIdWithPermissions(int $cashierGroupId, int $companyId): CashierGroup
    {
        return CashierGroup::with('permissions')
            ->select(
                'id',
                'name',
                'price_override_type',
                'price_override_limit_percentage_for_item',
                'price_override_limit_percentage_for_cart'
            )
            ->where('company_id', $companyId)
            ->findOrFail($cashierGroupId);
    }

    public function update(CashierGroupData $cashierGroupData, int $cashierGroupId, int $companyId): void
    {
        $cashierGroup = $this->getByIdWithPermissions($cashierGroupId, $companyId);

        $cashierGroupRecord = $cashierGroupData->all();
        $cashierGroupRecord['company_id'] = $companyId;
        unset($cashierGroupRecord['permission_ids']);

        $cashierGroup->update($cashierGroupRecord);

        $cashierGroup->permissions()->delete();

        $permissionIds = collect($cashierGroupData->permission_ids)->map(fn ($permissionId): array => [
            'permission_id' => $permissionId,
        ])->toArray();

        $cashierGroup->permissions()->createMany($permissionIds);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return CashierGroup::select(
            'id',
            'name',
            'price_override_limit_percentage_for_item',
            'price_override_type',
            'price_override_limit_percentage_for_cart'
        )
            ->where('company_id', $companyId)
            ->get();
    }

    public function getIdAndPriceOverrideLimitPercentageColumnName(): string
    {
        return 'id,price_override_limit_percentage_for_item,price_override_limit_percentage_for_cart,price_override_type';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,price_override_limit_percentage_for_cart,price_override_limit_percentage_for_item,price_override_type';
    }

    public function getCashierGroupsExport(array $filterData, int $companyId): Collection
    {
        return $this->cashierGroupQuery($filterData, $companyId)->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return CashierGroup::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getIdByName(string $name): int
    {
        /** @var CashierGroup $cashierGroup */
        $cashierGroup = CashierGroup::where('name', $name)->first();

        return $cashierGroup->id;
    }

    public function updateByName(array $cashierGroupData, string $name, int $companyId): void
    {
        /** @var array $permissionIds */
        $permissionIds = $cashierGroupData['permission_ids'];
        unset($cashierGroupData['permission_ids']);

        $cashierGroup = CashierGroup::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first();

        if ($cashierGroup instanceof CashierGroup) {
            $cashierGroup->update($cashierGroupData);
            $cashierGroup->permissions()->delete();

            $ids = collect($permissionIds)->map(fn ($permissionId): array => [
                'permission_id' => $permissionId,
            ])->toArray();

            $cashierGroup->permissions()->createMany($ids);
        }
    }

    private function cashierGroupQuery(array $filterData, int $companyId): Builder
    {
        return CashierGroup::query()
            ->select(
                'id',
                'name',
                'company_id',
                'price_override_limit_percentage_for_item',
                'price_override_type',
                'price_override_limit_percentage_for_cart'
            )
            ->with('permissions')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny([
                        'name',
                        'price_override_limit_percentage_for_item',
                        'price_override_limit_percentage_for_cart',
                    ], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
