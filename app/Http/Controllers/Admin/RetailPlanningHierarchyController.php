<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\RetailPlanningHierarchy\RetailPlanningHierarchyQueries;
use Illuminate\Support\Collection;

class RetailPlanningHierarchyController
{
    public function __construct(
        protected RetailPlanningHierarchyQueries $retailPlanningHierarchyQueries
    ) {
    }

    public function getChildHierarchies(int $parentHierarchyId): Collection
    {
        return $this->retailPlanningHierarchyQueries->getChildHierarchies(
            session('admin_company_id'),
            $parentHierarchyId
        );
    }
}
