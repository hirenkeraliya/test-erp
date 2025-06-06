<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\DreamPrice\DataObjects\StoreManagerApiDreamPriceData;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Resources\ApplicationDreamPriceListResource;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DreamPriceController extends Controller
{
    public function getDreamPrices(
        Request $request,
        StoreManagerApiDreamPriceData $storeManagerApiDreamPriceData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $selectedDate = $storeManagerApiDreamPriceData->selected_date ?: Carbon::now()->format('Y-m-d');

        $filterData = [
            'sort_by' => $storeManagerApiDreamPriceData->sort_by,
            'sort_direction' => $storeManagerApiDreamPriceData->sort_direction,
            'per_page' => $storeManagerApiDreamPriceData->per_page,
            'selected_date' => $selectedDate,
            'search_text' => $storeManagerApiDreamPriceData->search_text,
            'location_id' => $storeManagerApiDreamPriceData->store_id ?? $storeManagerApiDreamPriceData->location_id,
            'dream_price_ids' => $storeManagerApiDreamPriceData->dream_price_ids ? explode(
                ',',
                $storeManagerApiDreamPriceData->dream_price_ids
            ) : null,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);
        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPrices = $dreamPriceQueries->getDreamPricesForApplication($filterData, $companyId);

        return [
            'data' => ApplicationDreamPriceListResource::collection($dreamPrices->getCollection()),
            'total_records' => $dreamPrices->total(),
            'last_page' => $dreamPrices->lastPage(),
            'current_page' => $dreamPrices->currentPage(),
            'per_page' => $dreamPrices->perPage(),
        ];
    }
}
