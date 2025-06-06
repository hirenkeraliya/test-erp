<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\EmployeeGroup\DataObjects\PaginatedEmployeeGroupListDataForPos;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Resources\PosEmployeeGroupResource;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class EmployeeGroupController extends Controller
{
    /**
     * @return mixed[]
     */
    public function getPaginateEmployeeGroup(
        Request $request,
        PaginatedEmployeeGroupListDataForPos $paginatedEmployeeGroupListDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        $filteredData = [
            'per_page' => $paginatedEmployeeGroupListDataForPos->per_page,
            'sort_by' => $paginatedEmployeeGroupListDataForPos->sort_by,
            'search_text' => $paginatedEmployeeGroupListDataForPos->search_text,
            'sort_direction' => $paginatedEmployeeGroupListDataForPos->sort_direction,
            'after_updated_at' => $paginatedEmployeeGroupListDataForPos->after_updated_at,
        ];

        $employeeGroups = $employeeGroupQueries->listQuery($filteredData, $companyId);

        return [
            'employee_group' => PosEmployeeGroupResource::collection($employeeGroups),
            'total_records' => $employeeGroups->total(),
            'last_page' => $employeeGroups->lastPage(),
            'current_page' => $employeeGroups->currentPage(),
            'per_page' => $employeeGroups->perPage(),
        ];
    }
}
