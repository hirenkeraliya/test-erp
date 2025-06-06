<?php

declare(strict_types=1);

use App\Domains\RetailPlanningHierarchy\RetailPlanningHierarchyQueries;
use App\Http\Controllers\Admin\RetailPlanningHierarchyController;
use App\Models\RetailPlanningHierarchy;

it(
    'can fetch the child hierarchies of a parent',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $parentHierarchy = RetailPlanningHierarchy::factory()->make([
            'company_id' => $companyId,
        ]);

        $parentHierarchyId = 2;

        $childHierarchies = RetailPlanningHierarchy::factory(3)->childOf($parentHierarchy->id)->make([
            'company_id' => $companyId,
            'parent_id' => $parentHierarchyId,
        ]);

        $retailPlanningHierarchyQueries = $this->mock(RetailPlanningHierarchyQueries::class, function ($mock) use (
            $companyId,
            $parentHierarchyId,
            $childHierarchies,
        ): void {
            $mock->shouldReceive('getChildHierarchies')
                ->once()
                ->with($companyId, $parentHierarchyId)
                ->andReturn($childHierarchies);
        });

        $retailPlanningHierarchyController = new RetailPlanningHierarchyController($retailPlanningHierarchyQueries);

        $response = $retailPlanningHierarchyController->getChildHierarchies($parentHierarchyId);

        $firstChild = $childHierarchies->first();

        expect($response)->toBeCollection();

        expect($response->count())->toBe(3);

        expect($response->first()->toArray())
            ->toHaveKey('name', $firstChild->name)
            ->toHaveKey('parent_id', $parentHierarchyId)
            ->toHaveKey('company_id', $companyId);
    }
);
