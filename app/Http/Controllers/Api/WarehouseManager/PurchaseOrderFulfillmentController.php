<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderCheckRequestService;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForWarehouseManagerData;
use App\Domains\PurchaseOrderFulfillment\DataObjects\WarehouseManagerApiPurchaseOrderFulfillmentData;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Resource\PurchaseOrderFulfillmentListInternalApplicationResource;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentCheckRequestForInternalAppService;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\WarehouseManager\Services\WarehouseManagerService;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurchaseOrderFulfillmentController extends Controller
{
    public function __construct(
        protected PurchaseOrderFulfillmentQueries $purchaseOrderFulfillmentQueries
    ) {
    }

    public function getPaginatedDeliveryOrders(
        Request $request,
        WarehouseManagerApiPurchaseOrderFulfillmentData $warehouseManagerApiPurchaseOrderFulfillmentData
    ): array {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $dateRange = [
            $warehouseManagerApiPurchaseOrderFulfillmentData->start_date,
            $warehouseManagerApiPurchaseOrderFulfillmentData->end_date,
        ];

        /** @var int $locationId */
        $locationId = $warehouseManagerApiPurchaseOrderFulfillmentData->warehouse_id ??
            $warehouseManagerApiPurchaseOrderFulfillmentData->location_id;

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithStoreExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            (int) $warehouseManager->id,
            (int) $locationId
        );

        if (! $warehouseManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse((int) $locationId);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyId(
            $warehouseManagerApiPurchaseOrderFulfillmentData->purchase_order_id,
            $companyId
        );

        $purchaseOrderCheckRequestService = resolve(PurchaseOrderCheckRequestService::class);
        if (! $purchaseOrderCheckRequestService->canPurchaseOrderDeliveryOrder($purchaseOrder)) {
            abort(417, 'The Delivery Order cannot be accessed.');
        }

        $filterData = [
            'search_text' => $warehouseManagerApiPurchaseOrderFulfillmentData->search_text,
            'sort_by' => $warehouseManagerApiPurchaseOrderFulfillmentData->sort_by,
            'sort_direction' => $warehouseManagerApiPurchaseOrderFulfillmentData->sort_direction,
            'per_page' => $warehouseManagerApiPurchaseOrderFulfillmentData->per_page,
            'date_range' => $dateRange,
            'location_id' => $warehouseManagerApiPurchaseOrderFulfillmentData->warehouse_id ?? $warehouseManagerApiPurchaseOrderFulfillmentData->location_id,
            'select_status' => $warehouseManagerApiPurchaseOrderFulfillmentData->status,
        ];

        $lengthAwarePaginator = $this->purchaseOrderFulfillmentQueries->listQueryForInternalApplication(
            $filterData,
            $purchaseOrder->getKey(),
            $companyId
        );

        return [
            'data' => PurchaseOrderFulfillmentListInternalApplicationResource::collection(
                $lengthAwarePaginator->getCollection()
            ),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function addShippingDetails(
        Request $request,
        PurchaseOrderFulfillmentStoreForWarehouseManagerData $purchaseOrderFulfillmentStoreForWarehouseManagerData,
    ): void {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $warehouseManagerService = resolve(WarehouseManagerService::class);

        /** @var int $locationId */
        $locationId = $purchaseOrderFulfillmentStoreForWarehouseManagerData->warehouse_id ??
            $purchaseOrderFulfillmentStoreForWarehouseManagerData->location_id;

        $warehouseManagerService->checkAuthorizationForWarehouseManager($warehouseManager->id, (int) $locationId);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse((int) $locationId);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyId(
            $purchaseOrderFulfillmentStoreForWarehouseManagerData->purchase_order_id,
            $companyId
        );

        $productIds = collect($purchaseOrderFulfillmentStoreForWarehouseManagerData->transfer_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        [$products, $batches, $inventories, $derivatives] = $purchaseOrderFulfillmentService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $purchaseOrder->location_id,
        );

        $purchaseOrderFulfillmentCheckRequestForInternalAppService = resolve(
            PurchaseOrderFulfillmentCheckRequestForInternalAppService::class
        );
        $purchaseOrderFulfillmentCheckRequestForInternalAppService->checkRequestDetails(
            $purchaseOrderFulfillmentStoreForWarehouseManagerData,
            $products,
            $inventories,
            $batches
        );

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderItems = $purchaseOrderItemQueries->getByPurchaseOrderId(
            $purchaseOrderFulfillmentStoreForWarehouseManagerData->purchase_order_id,
            $companyId
        );

        $purchaseOrderItems = $this->getNonZeroQuantityPurchaseOrderItem($purchaseOrderItems);

        $sequenceQueries = resolve(SequenceQueries::class);
        $transferType = $purchaseOrderFulfillmentService->prepareTransferTypeForDeliveryNote(
            OrderTypes::SALES_ORDER->value
        );

        $sequence = $sequenceQueries->addNew($purchaseOrder->location_id, $transferType);

        DB::beginTransaction();

        try {
            if ($purchaseOrder->status === Statuses::APPROVED->value) {
                $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
                $purchaseOrderTransactionQueries->addNew(
                    $purchaseOrderFulfillmentStoreForWarehouseManagerData->purchase_order_id,
                    $purchaseOrder->status,
                    Statuses::PARTIAL_FULFILLMENT->value,
                    $warehouseManager
                );

                $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::PARTIAL_FULFILLMENT->value);
            }

            $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

            $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->addNew([
                'purchase_order_id' => $purchaseOrderFulfillmentStoreForWarehouseManagerData->purchase_order_id,
                'created_by_company_id' => $companyId,
                'happened_at' => $purchaseOrderFulfillmentStoreForWarehouseManagerData->happened_at,
                'notes' => $purchaseOrderFulfillmentStoreForWarehouseManagerData->notes,
                'delivery_order_number' => $sequence->getCompleteNumber(),
                'status' => FulfillmentStatuses::DRAFT->value,
            ]);

            $this->addPurchaseOrderFulfillmentItems(
                $purchaseOrderItems,
                $batches,
                $purchaseOrderFulfillmentStoreForWarehouseManagerData->transfer_items,
                $purchaseOrderFulfillment->getKey()
            );

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    private function getNonZeroQuantityPurchaseOrderItem(Collection $purchaseOrderItems): Collection
    {
        $purchaseOrderItems = $purchaseOrderItems->filter(
            fn ($purchaseOrderItem): bool => ($purchaseOrderItem->quantity - $purchaseOrderItem->rejected_quantity - $purchaseOrderItem->transferred_quantity) > 0
        );

        if ($purchaseOrderItems->isEmpty()) {
            abort(412, 'All items that were to be added to the Delivery Order have already been included');
        }

        return $purchaseOrderItems;
    }

    private function addPurchaseOrderFulfillmentItems(
        Collection $purchaseOrderItems,
        Collection $batches,
        array $transferItems,
        int $purchaseOrderFulfillmentId
    ): void {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);

        foreach ($transferItems as $transferItem) {
            if (! array_key_exists('transfer_quantity', $transferItem)) {
                continue;
            }

            if ($transferItem['transfer_quantity'] <= 0) {
                continue;
            }

            if (! array_key_exists('package_quantity', $transferItem)) {
                $transferItem['package_quantity'] = null;
            }

            if (! array_key_exists('package_total_quantity', $transferItem)) {
                $transferItem['package_total_quantity'] = null;
            }

            if (! array_key_exists('package_type_id', $transferItem)) {
                $transferItem['package_type_id'] = null;
            }

            $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->addNew([
                'purchase_order_fulfillment_id' => $purchaseOrderFulfillmentId,
                'purchase_order_item_id' => $transferItem['purchase_order_item_id'],
                'product_id' => $transferItem['product_id'],
                'transfer_quantity' => $transferItem['transfer_quantity'],
                'package_quantity' => $transferItem['package_quantity'],
                'package_total_quantity' => $transferItem['package_total_quantity'],
                'package_type_id' => $transferItem['package_type_id'],
                'remarks' => $transferItem['remarks'],
            ]);

            $purchaseOrderItem = $purchaseOrderItems->firstWhere('id', $transferItem['purchase_order_item_id']);

            $purchaseOrderItemQueries->updateTransferredQuantity(
                $purchaseOrderItem,
                (float) $transferItem['transfer_quantity']
            );

            if (array_key_exists('batch_details', $transferItem)) {
                $purchaseOrderFulfillmentService->updateBatches(
                    $purchaseOrderFulfillmentItem,
                    $batches,
                    $transferItem['batch_details']
                );
            }
        }
    }
}
