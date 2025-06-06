<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\SaleTarget\DataObjects\SalesTargetListDataForPromoterApp;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Resources\SalesTargetDetailsResourceForPromoterApp;
use App\Domains\SaleTarget\Resources\SalesTargetListResourceForPromoterApp;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use Illuminate\Http\Request;

class SalesTargetController extends Controller
{
    public function getTimeIntervalTypes(): array
    {
        return [
            'time_interval_types' => TimeIntervalType::getList(),
        ];
    }

    public function getSalesTargets(
        Request $request,
        SalesTargetListDataForPromoterApp $salesTargetListDataForPromoterApp
    ): array {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $filterData = [
            'time_interval_type' => $salesTargetListDataForPromoterApp->time_interval_type_id,
            'promoter_id' => $promoter->id,
            'search_text' => $salesTargetListDataForPromoterApp->search_text,
            'sort_by' => $salesTargetListDataForPromoterApp->sort_by,
            'sort_direction' => $salesTargetListDataForPromoterApp->sort_direction,
            'per_page' => $salesTargetListDataForPromoterApp->per_page,
            'target_type' => TargetType::PROMOTER_WISE->value,
            'select_status' => Statuses::ACTIVE->value,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $salesTargetQueries = resolve(SaleTargetQueries::class);

        $salesTargets = $salesTargetQueries->getPaginatedListForPromoterApp($filterData, $companyId);

        return [
            'sales_targets' => SalesTargetListResourceForPromoterApp::collection($salesTargets),
            'total_records' => $salesTargets->total(),
            'last_page' => $salesTargets->lastPage(),
            'current_page' => $salesTargets->currentPage(),
            'per_page' => $salesTargets->perPage(),
        ];
    }

    public function getSalesTargetDetails(Request $request, int $salesTargetId): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $salesTargetQueries = resolve(SaleTargetQueries::class);

        $salesTarget = $salesTargetQueries->getByIdForPromoterApp((int) $promoter->id, $salesTargetId, $companyId);

        return [
            'sales_target' => new SalesTargetDetailsResourceForPromoterApp($salesTarget),
        ];
    }
}
