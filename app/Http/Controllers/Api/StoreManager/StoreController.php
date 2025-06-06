<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\DataObjects\StoreManagerApiStoreStockData;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\StoreWiseProductStockResource;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Store\Resources\StoreListResource;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function getStores(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $validatedData = $request->validate([
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $filterData = [
            'search_text' => $validatedData['search_text'] ?? null,
        ];

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadStoresWithSearch($storeManager, $filterData);

        return [
            'stores' => StoreListResource::collection($storeManager->getLocations()),
            'locations' => StoreListResource::collection($storeManager->getLocations()),
        ];
    }

    public function getStoreStock(
        Request $request,
        StoreManagerApiStoreStockData $storeManagerApiStoreStockData,
        int $productId
    ): array {
        $filteredData = [
            'per_page' => $storeManagerApiStoreStockData->per_page,
            'sort_by' => $storeManagerApiStoreStockData->sort_by,
            'sort_direction' => $storeManagerApiStoreStockData->sort_direction,
            'search_text' => $storeManagerApiStoreStockData->search_text,
        ];

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoryStocksForApplication(
            $filteredData,
            LocationTypes::STORE->value,
            $companyId,
            $productId
        );

        return [
            'store_stock' => StoreWiseProductStockResource::collection($inventory),
            'location_stock' => StoreWiseProductStockResource::collection($inventory),
            'total_records' => $inventory->total(),
            'last_page' => $inventory->lastPage(),
            'current_page' => $inventory->currentPage(),
            'per_page' => $inventory->perPage(),
        ];
    }
}
