<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\SaleTarget\DataObjects\SalesTargetListDataForStoreManagerApp;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Resources\SalesTargetDetailsByPromoterResourceForStoreManagerApp;
use App\Domains\SaleTarget\Resources\SalesTargetDetailsResourceForStoreManagerApp;
use App\Domains\SaleTarget\Resources\SalesTargetListResourceForStoreManagerApp;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
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
        SalesTargetListDataForStoreManagerApp $salesTargetListDataForStoreManagerApp
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filterData = [
            'time_interval_type' => $salesTargetListDataForStoreManagerApp->time_interval_type_id,
            'location_id' => $salesTargetListDataForStoreManagerApp->store_id ?? $salesTargetListDataForStoreManagerApp->location_id,
            'search_text' => $salesTargetListDataForStoreManagerApp->search_text,
            'sort_by' => $salesTargetListDataForStoreManagerApp->sort_by,
            'sort_direction' => $salesTargetListDataForStoreManagerApp->sort_direction,
            'per_page' => $salesTargetListDataForStoreManagerApp->per_page,
            'target_type' => TargetType::STORE_WISE->value,
            'select_status' => Statuses::ACTIVE->value,
        ];

        /** @var int $locationId */
        $locationId = $salesTargetListDataForStoreManagerApp->store_id ?? $salesTargetListDataForStoreManagerApp->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId,
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $salesTargets = $saleTargetQueries->getPaginatedListForStoreManager($filterData, $companyId);

        return [
            'sales_targets' => SalesTargetListResourceForStoreManagerApp::collection($salesTargets),
            'total_records' => $salesTargets->total(),
            'last_page' => $salesTargets->lastPage(),
            'current_page' => $salesTargets->currentPage(),
            'per_page' => $salesTargets->perPage(),
        ];
    }

    public function getSalesTargetDetails(Request $request, int $salesTargetId, int $locationId): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId,
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $salesTarget = $saleTargetQueries->getByIdForStoreManagerApp($locationId, $salesTargetId, $companyId);

        return [
            'sales_target' => new SalesTargetDetailsResourceForStoreManagerApp($salesTarget),
        ];
    }

    public function getSalesTargetsByPromoter(
        Request $request,
        SalesTargetListDataForStoreManagerApp $salesTargetListDataForStoreManagerApp
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filterData = [
            'time_interval_type' => $salesTargetListDataForStoreManagerApp->time_interval_type_id,
            'location_id' => $salesTargetListDataForStoreManagerApp->store_id ?? $salesTargetListDataForStoreManagerApp->location_id,
            'search_text' => $salesTargetListDataForStoreManagerApp->search_text,
            'sort_by' => $salesTargetListDataForStoreManagerApp->sort_by,
            'sort_direction' => $salesTargetListDataForStoreManagerApp->sort_direction,
            'per_page' => $salesTargetListDataForStoreManagerApp->per_page,
            'target_type' => TargetType::PROMOTER_WISE->value,
            'select_status' => Statuses::ACTIVE->value,
        ];

        /** @var int $locationId */
        $locationId = $salesTargetListDataForStoreManagerApp->store_id ?? $salesTargetListDataForStoreManagerApp->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId,
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $salesTargets = $saleTargetQueries->getPaginatedListByPromoter($filterData, $companyId);

        return [
            'sales_targets' => SalesTargetListResourceForStoreManagerApp::collection($salesTargets),
            'total_records' => $salesTargets->total(),
            'last_page' => $salesTargets->lastPage(),
            'current_page' => $salesTargets->currentPage(),
            'per_page' => $salesTargets->perPage(),
        ];
    }

    public function getSalesTargetDetailsByPromoter(Request $request, int $salesTargetId, int $locationId): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId,
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $salesTarget = $saleTargetQueries->getIdByPromoter($locationId, $salesTargetId, $companyId);

        return [
            'sales_target' => new SalesTargetDetailsByPromoterResourceForStoreManagerApp($salesTarget),
        ];
    }
}
