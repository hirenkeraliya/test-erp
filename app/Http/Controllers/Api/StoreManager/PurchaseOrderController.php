<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\DataObjects\StoreManagerApiPurchaseOrderData;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Resource\PurchaseOrderListResource;
use App\Domains\PurchaseOrderItem\DataObjects\StoreManagerApiPurchaseOrderItemData;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderItem\Resource\PurchaseOrderItemsResource;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function getPaginatedPurchaseOrders(
        Request $request,
        StoreManagerApiPurchaseOrderData $storeManagerApiPurchaseOrderData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $dateRange = [$storeManagerApiPurchaseOrderData->start_date, $storeManagerApiPurchaseOrderData->end_date];

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $storeManagerApiPurchaseOrderData->id
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($storeManagerApiPurchaseOrderData->id);

        $filterData = [
            'search_text' => $storeManagerApiPurchaseOrderData->search_text,
            'sort_by' => $storeManagerApiPurchaseOrderData->sort_by,
            'sort_direction' => $storeManagerApiPurchaseOrderData->sort_direction,
            'per_page' => $storeManagerApiPurchaseOrderData->per_page,
            'date_range' => $dateRange,
            'order_type' => $storeManagerApiPurchaseOrderData->order_type,
            'select_status' => $storeManagerApiPurchaseOrderData->status,
            'order_number' => null,
            'external_company_id' => null,
            'external_location_id' => null,
            'location_id' => $storeManagerApiPurchaseOrderData->id,
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
        StoreManagerApiPurchaseOrderItemData $storeManagerApiPurchaseOrderItemData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        /** @var int $locationId */
        $locationId = $storeManagerApiPurchaseOrderItemData->store_id ?? $storeManagerApiPurchaseOrderItemData->location_id;

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId(
            (int) $storeManager->id,
            $locationId
        );

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderItems = $purchaseOrderItemQueries->getPaginatedByPurchaseOrderId(
            $storeManagerApiPurchaseOrderItemData->all(),
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
