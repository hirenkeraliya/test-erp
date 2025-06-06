<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup;

use App\Domains\Category\CategoryQueries;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\DataObjects\EcommerceMemberGroupData;
use App\Domains\MemberGroup\DataObjects\MemberGroupData;
use App\Domains\MemberGroup\Enums\DateConditionTypes;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\Enums\NumberConditionTypes;
use App\Domains\MemberGroup\Enums\SmartGroupTypes;
use App\Domains\MemberGroup\Events\MemberGroupUpdateEvent;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use App\Domains\Product\ProductQueries;
use App\Models\Member;
use App\Models\MemberGroup;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MemberGroupQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->memberGroupQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getListWithMemberIds(array $filterData, int $companyId): LengthAwarePaginator
    {
        $memberQueries = resolve(MemberQueries::class);

        return MemberGroup::query()
            ->select('id', 'name', 'code', 'created_at', 'updated_at')
            ->where('company_id', $companyId)
            ->with(['memberGroupMembers.members:' . $memberQueries->getCompanyIdColumn()])
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
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(MemberGroupData $memberGroupData, int $companyId): MemberGroup
    {
        $memberGroupRecord = $memberGroupData->all();
        unset($memberGroupRecord['member_file'], $memberGroupRecord['product_file']);
        $memberGroup = MemberGroup::create([
            'name' => $memberGroupRecord['name'],
            'code' => $memberGroupRecord['code'],
            'type_id' => $memberGroupRecord['type_id'],
            'members_count' => 0,
            'company_id' => $companyId,
        ]);

        $memberGroupDetails = $this->fixUpdateGroupColumnsValues($memberGroupData->toArray());
        unset($memberGroupDetails['product_ids'], $memberGroupDetails['category_ids'], $memberGroupDetails['member_file'], $memberGroupDetails['product_file']);
        $memberGroup->update($memberGroupDetails);
        if ($memberGroupDetails['type_id'] == GroupTypes::MANUAL_GROUP->value) {
            $this->manualGroup($memberGroup, $memberGroupDetails);
        } elseif ($memberGroupDetails['type_id'] == GroupTypes::SMART_GROUP->value) {
            $this->smartGroup($memberGroup, $memberGroupData);
        }

        event(new MemberGroupUpdateEvent($memberGroup));

        return $memberGroup;
    }

    public function getById(int $memberGroupId, int $companyId): MemberGroup
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return MemberGroup::select(
            'id',
            'name',
            'code',
            'type_id',
            'smart_group_type_id',
            'date_condition_type_id',
            'element_condition_type_id',
            'number_condition_type_id',
            'date',
            'max_date',
            'value',
            'max_value',
            'members_count'
        )
             ->with([
                 'products:' . $productQueries->getBasicColumnNames(),
                 'categories:' . $categoryQueries->getBasicColumnNames(),
                 'importRecord:' . $importRecordQueries->getModuleWithStatusColumns(),
                 'memberGroupMembers:id,member_group_id,member_id',
                 'memberGroupMembers.member:' . $memberQueries->getBasicColumnNamesForPosSale(),
             ])
            ->where('company_id', $companyId)
            ->findOrFail($memberGroupId);
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?MemberGroup
    {
        return MemberGroup::select('id')
            ->whereDoesntHave('memberGroupChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?MemberGroup
    {
        return MemberGroup::select('id')
            ->whereDoesntHave('memberGroupChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getMemberGroupEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId
    ): Collection {
        return MemberGroup::select('id', 'name', 'code')
        ->whereDoesntHave('memberGroupChannelReferences', function ($query) use ($saleChannelId): void {
            $query->where('sale_channel_id', $saleChannelId);
        })
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getByIdForImportRecord(int $memberGroupId, int $companyId): MemberGroup
    {
        $memberGroupMembersQueries = resolve(MemberGroupMemberQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return MemberGroup::select(
            'id',
            'name',
            'code',
            'type_id',
            'smart_group_type_id',
            'date_condition_type_id',
            'element_condition_type_id',
            'number_condition_type_id',
            'date',
            'max_date',
            'value',
            'max_value',
            'members_count'
        )
             ->with([
                 'memberGroupMembers:' . $memberGroupMembersQueries->getBasicColumnNames(),
                 'products:' . $productQueries->getCommonRelationColumns(),
             ])
            ->where('company_id', $companyId)
            ->findOrFail($memberGroupId);
    }

    public function update(MemberGroupData $memberGroupData, int $memberGroupId, int $companyId): MemberGroup
    {
        $memberGroup = $this->getById($memberGroupId, $companyId);
        $this->detachCategoriesToMemberGroup($memberGroup);
        $previousMemberGroupTypeId = $memberGroup->type_id;

        $memberGroupDetails = $this->fixUpdateGroupColumnsValues($memberGroupData->toArray());
        unset($memberGroupDetails['product_ids'], $memberGroupDetails['category_ids'], $memberGroupDetails['member_file'], $memberGroupDetails['product_file']);
        $memberGroup->update($memberGroupDetails);
        if ($memberGroupDetails['type_id'] == GroupTypes::MANUAL_GROUP->value) {
            $this->manualGroup($memberGroup, $memberGroupDetails);
            $memberGroup->products()->detach();
            if ($previousMemberGroupTypeId == GroupTypes::SMART_GROUP->value) {
                $memberGroup->memberGroupMembers()->delete();
            }
        } elseif ($memberGroupDetails['type_id'] == GroupTypes::SMART_GROUP->value) {
            $this->smartGroup($memberGroup, $memberGroupData);
            if ($memberGroupDetails['smart_group_type_id'] != SmartGroupTypes::ITEM->value) {
                $memberGroup->products()->detach();
            }

            $memberGroup->memberGroupMembers()->delete();
        }

        event(new MemberGroupUpdateEvent($memberGroup));

        return $memberGroup;
    }

    public function getMemberGroupsExport(array $filterData, int $companyId): Collection
    {
        return $this->memberGroupQuery($filterData, $companyId)->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return MemberGroup::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('type_id', GroupTypes::MANUAL_GROUP->value)
            ->get();
    }

    public function getAllByCompanyId(int $companyId): Collection
    {
        return MemberGroup::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function setMemberGroupUpdateAt(int $memberGroupId): void
    {
        $memberGroup = MemberGroup::findOrFail($memberGroupId);
        $memberGroup->touch();
    }

    public function addProductInPivot(int $productId, MemberGroup $memberGroup): void
    {
        $memberGroup->products()->attach($productId);
    }

    public function removeSelectedMembers(int $memberGroupId, int $companyId): void
    {
        $memberGroup = $this->getById($memberGroupId, $companyId);
        foreach ($memberGroup->memberGroupMembers as $memberGroupMember) {
            $memberGroupMember->delete();
        }
    }

    public function removeSelectedProducts(int $memberGroupId, int $companyId): void
    {
        $memberGroup = $this->getById($memberGroupId, $companyId);
        $memberGroup->products()->detach();
    }

    public function getMemberGroup(int $memberGroupId, int $companyId): MemberGroup
    {
        return MemberGroup::select('id')
            ->where('company_id', $companyId)
            ->findOrFail($memberGroupId);
    }

    public function geSmartMemberGroupsByCompanyId(int $companyId): Collection
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);

        return MemberGroup::select('id', 'company_id')
            ->where('company_id', $companyId)
            ->where('type_id', GroupTypes::SMART_GROUP->value)
            ->with(['importRecord:' . $importRecordQueries->getModuleWithStatusColumns()])
            ->get();
    }

    public function getMatchMembersOfMemberGroup(MemberGroup $memberGroup, ?int $memberId = null): Collection
    {
        $memberQueries = resolve(MemberQueries::class);

        $members = collect([]);

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::PURCHASE_COUNT->value && $memberGroup->value && $memberGroup->number_condition_type_id) {
            $members = $memberQueries->getPurchaseCountByMembers(
                (float) $memberGroup->value,
                (float) $memberGroup->max_value ?: 0,
                $memberGroup->number_condition_type_id,
                $memberId,
            );
        }

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::LIFETIME_SPENT->value && $memberGroup->value && $memberGroup->number_condition_type_id) {
            $members = $memberQueries->getTotalSpentByMembers(
                (float) $memberGroup->value,
                (float) $memberGroup->max_value ?: 0,
                $memberGroup->number_condition_type_id,
                $memberId,
            );
        }

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::PURCHASE_DATE->value && $memberGroup->date && $memberGroup->date_condition_type_id) {
            $members = $memberQueries->getPurchaseDateByMembers(
                $memberGroup->date,
                $memberGroup->max_date,
                $memberGroup->date_condition_type_id,
                $memberId
            );
        }

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::FIRST_VISIT_DATE->value && $memberGroup->date && $memberGroup->date_condition_type_id) {
            $members = $memberQueries->getFirstVisitDateByMembers(
                $memberGroup->date,
                $memberGroup->max_date,
                $memberGroup->date_condition_type_id,
                $memberId
            );
        }

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::LAST_VISIT_DATE->value && $memberGroup->date && $memberGroup->date_condition_type_id) {
            $members = $memberQueries->getLastVisitDateByMembers(
                $memberGroup->date,
                $memberGroup->max_date,
                $memberGroup->date_condition_type_id,
                $memberId
            );
        }

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::ITEM->value && $memberGroup->products->pluck(
            'id'
        )->toArray() && $memberGroup->element_condition_type_id) {
            $members = $memberQueries->getProductsIdByMembers(
                $memberGroup->products->pluck('id')->toArray(),
                $memberGroup->element_condition_type_id,
                $memberId,
            );
        }

        if ($memberGroup->smart_group_type_id == SmartGroupTypes::CATEGORY->value && $memberGroup->categories->pluck(
            'id'
        )->toArray() && $memberGroup->element_condition_type_id) {
            return $memberQueries->getCategoriesIdByMembers(
                $memberGroup->categories->pluck('id')->toArray(),
                $memberGroup->element_condition_type_id,
                $memberId,
            );
        }

        return $members;
    }

    public function getMatchMemberOfMemberGroup(MemberGroup $memberGroup, int $memberId): ?Member
    {
        return $this->getMatchMembersOfMemberGroup($memberGroup, $memberId)->first();
    }

    public function getItemTypeSmartMemberGroup(int $memberGroupId): ?MemberGroup
    {
        return MemberGroup::select('id', 'company_id')
            ->where('type_id', GroupTypes::SMART_GROUP->value)
            ->where('smart_group_type_id', SmartGroupTypes::ITEM->value)
            ->where('id', $memberGroupId)
            ->first();
    }

    public function getByOnlyId(int $memberGroupId): MemberGroup
    {
        return MemberGroup::select('id', 'company_id', 'name', 'code', 'type_id')
            ->findOrFail($memberGroupId);
    }

    public function addMemberGroupForEcommerce(
        EcommerceMemberGroupData $ecommerceMemberGroupData,
        int $companyId
    ): MemberGroup {
        $memberGroupRecord = $ecommerceMemberGroupData->all();
        $memberGroupRecord['company_id'] = $companyId;
        unset($memberGroupRecord['external_member_group_id']);

        return MemberGroup::create($memberGroupRecord);
    }

    public function updateForEcommerce(
        EcommerceMemberGroupData $ecommerceMemberGroupData,
        int $memberGroupId
    ): void {
        $memberGroup = MemberGroup::findOrFail($memberGroupId);
        $memberGroupRecord = $ecommerceMemberGroupData->all();

        unset($memberGroupRecord['external_member_group_id']);

        $memberGroup->update($memberGroupRecord);
    }

    public function existsByCode(string $code, ?int $memberGroupId = null): bool
    {
        return MemberGroup::select('id')->where('code', $code)
            ->when($memberGroupId, function ($query) use ($memberGroupId): void {
                $query->whereNot('id', $memberGroupId);
            })
            ->exists();
    }

    public function existsByName(string $name, ?int $memberGroupId = null): bool
    {
        return MemberGroup::select('id')->where('name', $name)
            ->when($memberGroupId, function ($query) use ($memberGroupId): void {
                $query->whereNot('id', $memberGroupId);
            })
            ->exists();
    }

    public function filterByType(int $typeId): Closure
    {
        return fn ($query) => $query->where('type_id', $typeId);
    }

    private function memberGroupQuery(array $filterData, int $companyId): Builder
    {
        $memberGroupMembersQueries = resolve(MemberGroupMemberQueries::class);
        $importRecordQueries = resolve(ImportRecordQueries::class);

        return MemberGroup::query()
            ->select('id', 'name', 'code', 'type_id')
            ->with([
                'memberGroupMembers.member:id,spent_till_now',
                'memberGroupMembers:' . $memberGroupMembersQueries->getBasicColumnNames(),
                'importRecord:' . $importRecordQueries->getModuleWithStatusColumns(),
            ])
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

    private function fixUpdateGroupColumnsValues(array $memberGroupDetails): array
    {
        if ($memberGroupDetails['type_id'] == GroupTypes::MANUAL_GROUP->value) {
            $memberGroupDetails['smart_group_type_id'] = null;
            $memberGroupDetails['date_condition_type_id'] = null;
            $memberGroupDetails['element_condition_type_id'] = null;
            $memberGroupDetails['number_condition_type_id'] = null;
            $memberGroupDetails['date'] = null;
            $memberGroupDetails['max_date'] = null;
            $memberGroupDetails['value'] = null;
            $memberGroupDetails['max_value'] = null;
            $memberGroupDetails['members_count'] = null;
        }

        if ($memberGroupDetails['type_id'] == GroupTypes::SMART_GROUP->value) {
            if (in_array(
                $memberGroupDetails['smart_group_type_id'],
                [SmartGroupTypes::PURCHASE_DATE->value,
                    SmartGroupTypes::FIRST_VISIT_DATE->value,
                    SmartGroupTypes::LAST_VISIT_DATE->value,
                ])) {
                $memberGroupDetails['element_condition_type_id'] = null;
                $memberGroupDetails['number_condition_type_id'] = null;
                $memberGroupDetails['value'] = null;
                $memberGroupDetails['max_value'] = null;
                $memberGroupDetails['members_count'] = null;
                if (in_array(
                    $memberGroupDetails['number_condition_type_id'],
                    [DateConditionTypes::MORE_THAN->value,
                        DateConditionTypes::LESS_THAN->value,
                        DateConditionTypes::EXACTLY_ON->value,
                    ])) {
                    $memberGroupDetails['max_date'] = null;
                }
            }

            if ($memberGroupDetails['smart_group_type_id'] == SmartGroupTypes::CATEGORY->value) {
                $memberGroupDetails['date_condition_type_id'] = null;
                $memberGroupDetails['number_condition_type_id'] = null;
                $memberGroupDetails['date'] = null;
                $memberGroupDetails['max_date'] = null;
                $memberGroupDetails['value'] = null;
                $memberGroupDetails['max_value'] = null;
                $memberGroupDetails['members_count'] = null;
            }

            if ($memberGroupDetails['smart_group_type_id'] == SmartGroupTypes::ITEM->value) {
                $memberGroupDetails['date_condition_type_id'] = null;
                $memberGroupDetails['number_condition_type_id'] = null;
                $memberGroupDetails['date'] = null;
                $memberGroupDetails['max_date'] = null;
                $memberGroupDetails['value'] = null;
                $memberGroupDetails['max_value'] = null;
                $memberGroupDetails['members_count'] = null;
            }

            if (in_array(
                $memberGroupDetails['smart_group_type_id'],
                [SmartGroupTypes::PURCHASE_COUNT->value, SmartGroupTypes::LIFETIME_SPENT->value])
            ) {
                $memberGroupDetails['date_condition_type_id'] = null;
                $memberGroupDetails['element_condition_type_id'] = null;
                $memberGroupDetails['date'] = null;
                $memberGroupDetails['max_date'] = null;
                $memberGroupDetails['members_count'] = null;
                if (in_array(
                    $memberGroupDetails['number_condition_type_id'],
                    [NumberConditionTypes::GREATER_THAN->value,
                        NumberConditionTypes::LESS_THAN->value,
                        NumberConditionTypes::EXACTLY_TO->value,
                    ])) {
                    $memberGroupDetails['max_value'] = null;
                }
            }
        }

        return $memberGroupDetails;
    }

    private function manualGroup(MemberGroup $memberGroup, array $memberGroupDetails): void
    {
        $memberGroupDetails['members_count'] = 0;
        $memberGroup->update($memberGroupDetails);
    }

    private function smartGroup(MemberGroup $memberGroup, MemberGroupData $memberGroupData): void
    {
        $memberGroupDetails = $memberGroupData->all();

        if ($memberGroupDetails['smart_group_type_id'] == SmartGroupTypes::CATEGORY->value) {
            $categories = $memberGroupDetails['category_ids'];
            $this->attachCategoriesToMemberGroup($memberGroup, $categories);
        }
    }

    private function attachCategoriesToMemberGroup(MemberGroup $memberGroup, array $categories): void
    {
        $memberGroup->categories()->attach($categories);
    }

    private function detachCategoriesToMemberGroup(MemberGroup $memberGroup): void
    {
        $memberGroup->categories()->detach();
    }
}
