<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\DataObjects\WarehouseManagerApiStoreStockData;
use App\Domains\Inventory\DataObjects\WarehouseManagerApiWarehouseStockData;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\WarehouseManagerStockListAPIResource;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Warehouse\Resources\WarehouseListResource;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function getWarehouses(Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $validatedData = $request->validate([
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $filterData = [
            'search_text' => $validatedData['search_text'] ?? null,
        ];

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManager = $warehouseManagerQueries->loadWarehouses($warehouseManager, $filterData);

        return [
            'warehouses' => WarehouseListResource::collection($warehouseManager->getLocations()),
            'locations' => WarehouseListResource::collection($warehouseManager->getLocations()),
        ];
    }

    public function getWarehouseStock(
        Request $request,
        WarehouseManagerApiWarehouseStockData $warehouseManagerApiWarehouseStockData,
        int $productId
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $filterData = [
            'sort_by' => $warehouseManagerApiWarehouseStockData->sort_by,
            'sort_direction' => $warehouseManagerApiWarehouseStockData->sort_direction,
            'per_page' => $warehouseManagerApiWarehouseStockData->per_page,
            'search_text' => $warehouseManagerApiWarehouseStockData->search_text,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoryStocksForApplication(
            $filterData,
            LocationTypes::WAREHOUSE->value,
            $companyId,
            $productId
        );

        return [
            'warehouse_stock' => WarehouseManagerStockListAPIResource::collection($inventory),
            'location_stock' => WarehouseManagerStockListAPIResource::collection($inventory),
            'total_records' => $inventory->total(),
            'last_page' => $inventory->lastPage(),
            'current_page' => $inventory->currentPage(),
            'per_page' => $inventory->perPage(),
        ];
    }

    public function getStoreStock(
        Request $request,
        WarehouseManagerApiStoreStockData $warehouseManagerApiStoreStockData,
        int $productId
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $filterData = [
            'sort_by' => $warehouseManagerApiStoreStockData->sort_by,
            'sort_direction' => $warehouseManagerApiStoreStockData->sort_direction,
            'per_page' => $warehouseManagerApiStoreStockData->per_page,
            'search_text' => $warehouseManagerApiStoreStockData->search_text,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoryStocksForApplication(
            $filterData,
            LocationTypes::STORE->value,
            $companyId,
            $productId
        );

        return [
            'store_stock' => WarehouseManagerStockListAPIResource::collection($inventory),
            'location_stock' => WarehouseManagerStockListAPIResource::collection($inventory),
            'total_records' => $inventory->total(),
            'last_page' => $inventory->lastPage(),
            'current_page' => $inventory->currentPage(),
            'per_page' => $inventory->perPage(),
        ];
    }
}
