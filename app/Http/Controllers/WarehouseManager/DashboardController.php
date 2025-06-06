<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Common\Services\DashboardService;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\SellingTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }

    public function getTransferOrder(): array
    {
        $transferOrders = [];
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'transfer_type' => null,
            'search_text' => null,
            'stock_transfer_date' => null,
            'select_status' => null,
        ];

        $transferOrderStatusCounts = $stockTransferQueries->transferOrderStatusCountForWarehouseManager(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_IN->value;
        $transferInOrderStatusCounts = $stockTransferQueries->transferOrderStatusCountForWarehouseManager(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_OUT->value;
        $transferOutOrderStatusCounts = $stockTransferQueries->transferOrderStatusCountForWarehouseManager(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferOrders[] = [
                'id' => $transferOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrderStatusCount->count,
                'transfer_in_count' => $transferInOrderStatusCounts->where(
                    'status',
                    $transferOrderStatusCount->status
                )->first()?->count,
                'transfer_out_count' => $transferOutOrderStatusCounts->where(
                    'status',
                    $transferOrderStatusCount->status
                )->first()?->count,
            ];
        }

        return [
            'transferOrders' => $transferOrders,
        ];
    }

    public function getRequestOrder(): array
    {
        $requestOrders = [];
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'transfer_type' => null,
            'search_text' => null,
            'stock_transfer_date' => null,
            'select_status' => null,
        ];

        $requestOrderStatusCounts = $stockTransferQueries->requestOrderStatusCountForWarehouseManager(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_IN->value;
        $transferInOrderStatusCounts = $stockTransferQueries->requestOrderStatusCountForWarehouseManager(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_OUT->value;
        $transferOutOrderStatusCounts = $stockTransferQueries->requestOrderStatusCountForWarehouseManager(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        foreach ($requestOrderStatusCounts as $requestOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($requestOrderStatusCount->status);
            $requestOrders[] = [
                'id' => $requestOrderStatusCount->status,
                'name' => $statusName,
                'count' => $requestOrderStatusCount->count,
                'transfer_in_count' => $transferInOrderStatusCounts->where(
                    'status',
                    $requestOrderStatusCount->status
                )->first()?->count,
                'transfer_out_count' => $transferOutOrderStatusCounts->where(
                    'status',
                    $requestOrderStatusCount->status
                )->first()?->count,
            ];
        }

        return [
            'requestOrders' => [] !== $requestOrders ? $requestOrders : null,
        ];
    }

    public function getTransferOut(): array
    {
        return [
            'transferOuts' => $this->preparedStockTransferCounts(TransferTypes::TRANSFER_OUT->value),
        ];
    }

    public function getTransferIn(): array
    {
        return [
            'transferIns' => $this->preparedStockTransferCounts(TransferTypes::TRANSFER_IN->value),
        ];
    }

    public function stockOverview(): Response
    {
        return Inertia::render('StockOverview', [
            'stockTypes' => [
                'no_stock' => Types::NO_STOCK->value,
                'low_stock_company' => Types::LOW_STOCK_COMPANY->value,
                'low_stock_location' => Types::LOW_STOCK_LOCATION->value,
                'low_stock_product' => Types::LOW_STOCK_PRODUCT->value,
                'negative_stock' => Types::NEGATIVE_STOCK->value,
            ],
            'transferTypes' => [
                'request_order' => StockTransferTypes::REQUEST_ORDER->value,
                'transfer_order' => StockTransferTypes::TRANSFER_ORDER->value,
            ],
            'activeStatus' => ProductStatuses::ACTIVE->value,
            'fulfillmentStatuses' => FulfillmentStatuses::generateStaticCasesArray(),
            'purchaseOrderStatuses' => Statuses::generateStaticCasesArray(),
            'stockTransferStatuses' => StatusTypes::generateStaticCasesArray(),
            'orderTypes' => OrderTypes::getFormattedArrayForStaticUse(),
            'sellingType' => SellingTypes::SELLING->value,
        ]);
    }

    public function getLowStockOverview(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
        ];

        $inventoryQueries = resolve(InventoryQueries::class);

        $lowStockCompanyCount = $inventoryQueries->getCompanyLowStockItems(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );
        $lowStockLocationCount = $inventoryQueries->getLocationLowStockItems(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );
        $lowStockProductCount = $inventoryQueries->getProductLowStockItems(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'lowStockCompanyCount' => $lowStockCompanyCount,
            'lowStockLocationCount' => $lowStockLocationCount,
            'lowStockProductCount' => $lowStockProductCount,
        ];
    }

    public function getNoStockStockOverview(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $noStockItemCount = $inventoryQueries->getNoStockItems(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'noStockItemCount' => $noStockItemCount,
        ];
    }

    public function getNegativeStockStockOverview(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $noStockItemCount = $inventoryQueries->getNegativeStockItems(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'negativeStockItemCount' => $noStockItemCount,
        ];
    }

    public function getPurchaseRequest(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $purchaseRequests = $dashboardService->getPurchaseRequestCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        $closedStatus = Statuses::getFormattedCaseName(Statuses::CLOSED->value);

        if (isset($purchaseRequests[$closedStatus])) {
            unset($purchaseRequests[$closedStatus]);
        }

        return [
            'purchaseRequests' => $purchaseRequests,
        ];
    }

    public function getTransferRequest(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'order_type' => OrderTypes::TRANSFER_REQUEST->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $transferRequests = $dashboardService->getPurchaseRequestCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        $closedStatus = Statuses::getFormattedCaseName(Statuses::CLOSED->value);

        if (isset($transferRequests[$closedStatus])) {
            unset($transferRequests[$closedStatus]);
        }

        return [
            'transferRequests' => $transferRequests,
        ];
    }

    public function getSalesOrder(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'order_type' => OrderTypes::SALES_ORDER->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $salesOrders = $dashboardService->getPurchaseOrderCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        $salesDeliveryOrders = $dashboardService->getPurchaseOrderFulfillmentCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'salesOrders' => $salesOrders,
            'salesDeliveryOrders' => $salesDeliveryOrders,
        ];
    }

    public function getPurchaseOrder(): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'order_type' => OrderTypes::PURCHASE_ORDER->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $purchaseOrders = $dashboardService->getPurchaseOrderCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        $purchaseDeliveryOrders = $dashboardService->getPurchaseOrderFulfillmentCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'purchaseOrders' => $purchaseOrders,
            'purchaseDeliveryOrders' => $purchaseDeliveryOrders,
        ];
    }

    private function preparedStockTransferCounts(int $transferType): array
    {
        $filterData = [
            'location_id' => session('warehouse_manager_selected_location_id'),
            'transfer_type' => $transferType,
        ];

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $transferOrderStatusCounts = $stockTransferQueries->storeManagerTransferInAndOutStatusCount(
            $filterData,
            session('warehouse_manager_selected_location_company_id'),
            session('warehouse_manager_selected_location_id')
        );

        $transferIns = [];

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferIns[] = [
                'id' => $transferOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrderStatusCount->count,
            ];
        }

        return $transferIns;
    }
}
