<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request): array
    {
        $filterData = $request->validate([
            'warehouse_id' => ['required_without:location_id', 'integer'],
            'location_id' => ['required_without:warehouse_id', 'integer'],
        ]);
        $filterData['location_id'] = $filterData['warehouse_id'] ?? $filterData['location_id'];

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $productQueries = resolve(ProductQueries::class);
        $skuCounts = $productQueries->getAllActiveProductsCount($companyId);

        $inventoryQueries = resolve(InventoryQueries::class);

        $productsWithNoStock = $inventoryQueries->getNoStockItems($filterData, $companyId);

        $negativeStockItemCount = $inventoryQueries->getNegativeStockItems($filterData, $companyId);
        $lowStockCompanyCount = $inventoryQueries->getCompanyLowStockItems($filterData, $companyId);
        $lowStockLocationCount = $inventoryQueries->getLocationLowStockItems($filterData, $companyId);
        $lowStockProductCount = $inventoryQueries->getProductLowStockItems($filterData, $companyId);

        return [
            'sku_managed' => $skuCounts,
            'no_stock' => $productsWithNoStock,
            'negative_stock_items' => $negativeStockItemCount,
            'low_stock_items_by_company' => $lowStockCompanyCount,
            'low_stock_items_by_location' => $lowStockLocationCount,
            'low_stock_items_by_product' => $lowStockProductCount,
        ];
    }

    public function getTransferStatusesData(Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $validatedData = $request->validate([
            'warehouse_id' => ['required_without:location_id', 'integer'],
            'location_id' => ['required_without:warehouse_id', 'integer'],
        ]);

        /** @var int $locationId */
        $locationId = $validatedData['warehouse_id'] ?? $validatedData['location_id'];

        $this->checkWarehouseAuthority($warehouseManager->id, (int) $locationId);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse((int) $locationId);

        $filterData = [
            'location_id' => (int) $locationId,
            'transfer_type' => null,
            'search_text' => null,
            'stock_transfer_date' => null,
            'select_status' => null,
        ];

        return [
            'transfer_orders' => $this->getTransferOrRequestOrders(
                [StockTransferTypes::TRANSFER_ORDER->value],
                $filterData,
                $companyId,
                (int) $locationId
            ),
            'request_orders' => $this->getTransferOrRequestOrders(
                [StockTransferTypes::REQUEST_ORDER->value],
                $filterData,
                $companyId,
                (int) $locationId
            ),
        ];
    }

    private function checkWarehouseAuthority(int $warehouseManagerId, int $locationId): void
    {
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            $warehouseManagerId,
            $locationId
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }
    }

    private function getTransferOrRequestOrders(
        array $stockTransferType,
        array $filterData,
        int $companyId,
        int $locationId
    ): array {
        $transferOrRequestOrders = [];

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $transferOrRequestOrderStatusCounts = $stockTransferQueries->warehouseManagerTransferOrRequestOrderStatusCount(
            $stockTransferType,
            $filterData,
            $companyId,
            $locationId
        );

        foreach ($transferOrRequestOrderStatusCounts as $transferOrRequestOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrRequestOrderStatusCount->status);
            $transferOrRequestOrders[] = [
                'id' => $transferOrRequestOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrRequestOrderStatusCount->count,
            ];
        }

        return $transferOrRequestOrders;
    }
}
