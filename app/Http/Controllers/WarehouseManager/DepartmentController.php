<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Department\DepartmentQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentQueries $departmentQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredDepartments(Request $request): array
    {
        return [
            'departments' => $this->departmentQueries->getFilteredDepartmentsByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getDepartmentsList(): array
    {
        return [
            'departments' => $this->departmentQueries->getWithBasicColumns(
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
