<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\DataObjects\StoreManagerApiCashbackData;
use App\Domains\Cashback\Resources\ApplicationCashbackListResource;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashbackController extends Controller
{
    public function getCashbacks(Request $request, StoreManagerApiCashbackData $storeManagerApiCashbackData): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $selectedDate = $storeManagerApiCashbackData->selected_date ?: Carbon::now()->format('Y-m-d');

        $filterData = [
            'sort_by' => $storeManagerApiCashbackData->sort_by,
            'sort_direction' => $storeManagerApiCashbackData->sort_direction,
            'per_page' => $storeManagerApiCashbackData->per_page,
            'location_ids' => $this->getLocationOrStore($storeManagerApiCashbackData),
            'selected_date' => $selectedDate,
            'search_text' => $storeManagerApiCashbackData->search_text,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $cashbackQueries = resolve(CashbackQueries::class);
        $cashbacks = $cashbackQueries->getCashbacksForApplication($filterData, $companyId);

        return [
            'data' => ApplicationCashbackListResource::collection($cashbacks->getCollection()),
            'total_records' => $cashbacks->total(),
            'last_page' => $cashbacks->lastPage(),
            'current_page' => $cashbacks->currentPage(),
            'per_page' => $cashbacks->perPage(),
        ];
    }

    public function getStoreWiseCashbacks(Request $request, int $locationId): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $cashbackQueries = resolve(CashbackQueries::class);
        $cashbacks = $cashbackQueries->getCashbacksStoreWiseForApplication($companyId, $locationId);

        return [
            'data' => ApplicationCashbackListResource::collection($cashbacks),
        ];
    }

    public function getLocationOrStore(StoreManagerApiCashbackData $storeManagerApiCashbackData): array
    {
        if ($storeManagerApiCashbackData->store_ids) {
            return explode(',', $storeManagerApiCashbackData->store_ids);
        }

        if ($storeManagerApiCashbackData->location_ids) {
            return explode(',', $storeManagerApiCashbackData->location_ids);
        }

        return [];
    }
}
