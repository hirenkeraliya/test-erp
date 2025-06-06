<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Counter\DataObjects\StoreManagerApiCloseCounterData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Resources\ClosedCounterReportListResource;
use App\Domains\CounterUpdate\Resources\StoreManagerAppClosedCounterDetailsResource;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class ClosedCounterReportController extends Controller
{
    public function getClosedCounters(
        Request $request,
        StoreManagerApiCloseCounterData $storeManagerApiCloseCounterData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $dateRange = [$storeManagerApiCloseCounterData->start_date, $storeManagerApiCloseCounterData->end_date];

        $filterData = [
            'search_text' => $storeManagerApiCloseCounterData->search_text,
            'sort_by' => $storeManagerApiCloseCounterData->sort_by,
            'sort_direction' => $storeManagerApiCloseCounterData->sort_direction,
            'per_page' => $storeManagerApiCloseCounterData->per_page,
            'counter_ids' => $storeManagerApiCloseCounterData->counter_ids,
            'cashier_id' => $storeManagerApiCloseCounterData->cashier_id,
            'date_range' => $dateRange,
            'closed_at' => $storeManagerApiCloseCounterData->closed_at,
        ];

        /** @var int $locationId */
        $locationId = $storeManagerApiCloseCounterData->store_id ?? $storeManagerApiCloseCounterData->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'The Provided Store is not accepted or allowed.');
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $counterUpdates = $counterUpdateQueries->getPaginatedClosedCounterListForStoreManager(
            $filterData,
            $companyId,
            $locationId
        );

        $totalSalesCollection = $counterUpdateQueries->closedCounterTotalSalesCollectionForStoreManager(
            $filterData,
            $companyId,
            $locationId
        );

        return [
            'data' => ClosedCounterReportListResource::collection($counterUpdates->getCollection()),
            'total_sales_collection' => $totalSalesCollection,
            'total_records' => $counterUpdates->total(),
            'last_page' => $counterUpdates->lastPage(),
            'current_page' => $counterUpdates->currentPage(),
            'per_page' => $counterUpdates->perPage(),
        ];
    }

    /**
     * @return array<string, StoreManagerAppClosedCounterDetailsResource>
     */
    public function getClosedCounterDetails(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $validatedData = $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
            'id' => ['required', 'integer'],
        ]);

        $locationId = $validatedData['store_id'] ?? $validatedData['location_id'];

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            (int) $locationId
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'The Provided Store is not accepted or allowed.');
        }

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterUpdateDetails = $counterUpdateQueries->findByIdFilterByCompanyAndStore(
            (int) $validatedData['id'],
            $companyId,
            (int) $locationId,
        );

        if (! $counterUpdateDetails) {
            abort(412, 'Specified id is not valid.');
        }

        return [
            'closed_counter_update_details' => new StoreManagerAppClosedCounterDetailsResource($counterUpdateDetails),
        ];
    }
}
