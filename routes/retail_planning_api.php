<?php

use App\Http\Controllers\Admin\RetailPlanningHierarchyController;
use Illuminate\Support\Facades\Route;

Route::controller(RetailPlanningHierarchyController::class)->name('retail_planning_hierarchies.')->group(
    function (): void {
        Route::get('retail-planning-hierarchies/{parentHierarchyId}/children', 'getChildHierarchies')->name(
            'get_child_hierarchies'
        );
    }
);
