<?php

declare(strict_types=1);

namespace App\Domains\DynamicMenus;

use App\Domains\DynamicMenus\DataObjects\DynamicMenuData;
use App\Models\DynamicMenu;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DynamicMenuQueries
{
    public function listQuery(array $filterData, int $companyId): Collection
    {
        return DynamicMenu::query()
            ->select('id', 'title', 'slug', 'company_id', 'parent_id', 'type', 'module_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('title', 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->whereNull('parent_id')
            ->where('company_id', $companyId)
            ->with('children:id,company_id,parent_id,title,slug,type,module_id,content')
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function addNew(DynamicMenuData $dynamicMenuData, int $companyId): void
    {
        $data = $dynamicMenuData->all();
        $data['slug'] = Str::slug($data['title']);
        $data['company_id'] = $companyId;

        DynamicMenu::create($data);
    }

    public function getById(int $dynamicMenuId): DynamicMenu
    {
        return DynamicMenu::select(
            'id',
            'title',
            'slug',
            'company_id',
            'parent_id',
            'type',
            'module_id',
            'content',
            'status'
        )
            ->findOrFail($dynamicMenuId);
    }

    public function update(DynamicMenuData $dynamicMenuData, int $id, int $companyId): void
    {
        $dynamicMenu = DynamicMenu::query()
            ->select('id', 'title', 'slug', 'content', 'company_id', 'status')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $requestData = $dynamicMenuData->all();
        $requestData['slug'] = Str::slug($requestData['title']);

        $dynamicMenu->fill($requestData);
        $statusChanged = $dynamicMenu->isDirty('status');

        $dynamicMenu->update($requestData);
        if ($statusChanged) {
            $this->updateAllDescendantStatuses($dynamicMenu->id, $requestData['status']);
        }
    }

    public function getChildCount(int $id): int
    {
        return DynamicMenu::query()
            ->where('parent_id', $id)
            ->count();
    }

    private function updateAllDescendantStatuses(int $parentId, bool $status): void
    {
        $children = DynamicMenu::where('parent_id', $parentId)->get();

        foreach ($children as $child) {
            $child->status = (int) $status;
            $child->save();

            $this->updateAllDescendantStatuses($child->id, $status);
        }
    }
}
