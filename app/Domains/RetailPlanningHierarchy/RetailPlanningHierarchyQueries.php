<?php

declare(strict_types=1);

namespace App\Domains\RetailPlanningHierarchy;

use App\Models\RetailPlanningHierarchy;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RetailPlanningHierarchyQueries
{
    private function getWithBasicColumnsQuery(int $companyId): Builder
    {
        return RetailPlanningHierarchy::select('id', 'name', 'parent_id')
            ->where('company_id', $companyId);
    }

    public function getTopLevelHierarchies(int $companyId): Collection
    {
        return $this->getWithBasicColumnsQuery($companyId)
            ->whereNull('parent_id')
            ->get();
    }

    public function getChildHierarchies(int $companyId, int $parentHierarchyId): Collection
    {
        return $this->getWithBasicColumnsQuery($companyId)
            ->where('parent_id', $parentHierarchyId)
            ->get();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }
}
