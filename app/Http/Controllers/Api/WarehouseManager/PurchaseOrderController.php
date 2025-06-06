<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\DataObjects\WarehouseManagerApiPurchaseOrderData;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Resource\PurchaseOrderListResource;
use App\Domains\PurchaseOrderItem\DataObjects\WarehouseManagerApiPurchaseOrderItemData;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderItem\Resource\PurchaseOrderItemsResource;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function getPaginatedPurchaseOrders(
        Request $request,
        WarehouseManagerApiPurchaseOrderData $warehouseManagerApiPurchaseOrderData
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $dateRange = [
            $warehouseManagerApiPurchaseOrderData->start_date,
            $warehouseManagerApiPurchaseOrderData->end_date,
        ];

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            $warehouseManagerApiPurchaseOrderData->id
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse($warehouseManagerApiPurchaseOrderData->id);

        $filterData = [
            'search_text' => $warehouseManagerApiPurchaseOrderData->search_text,
            'sort_by' => $warehouseManagerApiPurchaseOrderData->sort_by,
            'sort_direction' => $warehouseManagerApiPurchaseOrderData->sort_direction,
            'per_page' => $warehouseManagerApiPurchaseOrderData->per_page,
            'date_range' => $dateRange,
            'order_type' => $warehouseManagerApiPurchaseOrderData->order_type,
            'select_status' => $warehouseManagerApiPurchaseOrderData->status,
            'order_number' => null,
            'external_company_id' => null,
            'external_location_id' => null,
            'location_id' => $warehouseManagerApiPurchaseOrderData->id,
        ];

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrders = $purchaseOrderQueries->listQuery($filterData, $companyId);

        return [
            'data' => PurchaseOrderListResource::collection($purchaseOrders->getCollection()),
            'total_records' => $purchaseOrders->total(),
            'last_page' => $purchaseOrders->lastPage(),
            'current_page' => $purchaseOrders->currentPage(),
            'per_page' => $purchaseOrders->perPage(),
        ];
    }

    public function getItemsByPurchaseOrderId(
        Request $request,
        WarehouseManagerApiPurchaseOrderItemData $warehouseManagerApiPurchaseOrderItemData
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        /** @var int $locationId */
        $locationId = $warehouseManagerApiPurchaseOrderItemData->warehouse_id ??
            $warehouseManagerApiPurchaseOrderItemData->location_id;

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            (int) $locationId
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse($locationId);

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderItems = $purchaseOrderItemQueries->getPaginatedByPurchaseOrderId(
            $warehouseManagerApiPurchaseOrderItemData->all(),
            $companyId
        );

        return [
            'purchase_order_items' => PurchaseOrderItemsResource::collection($purchaseOrderItems),
            'last_page' => $purchaseOrderItems->lastPage(),
            'current_page' => $purchaseOrderItems->currentPage(),
            'per_page' => $purchaseOrderItems->perPage(),
        ];
    }

    public function getStatuses(): array
    {
        return [
            'statuses' => Statuses::getList(),
        ];
    }

    public function getOrderTypes(): array
    {
        return [
            'order_types' => OrderTypes::getList(),
        ];
    }
}
