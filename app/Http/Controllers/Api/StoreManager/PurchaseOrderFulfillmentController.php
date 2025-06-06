<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderCheckRequestService;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentStoreForStoreManagerData;
use App\Domains\PurchaseOrderFulfillment\DataObjects\StoreManagerApiPurchaseOrderFulfillmentData;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Resource\PurchaseOrderFulfillmentListInternalApplicationResource;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentCheckRequestForInternalAppService;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StoreManager\Services\StoreManagerService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
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
        StoreManagerApiPurchaseOrderFulfillmentData $storeManagerApiPurchaseOrderFulfillmentData
    ): array {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $dateRange = [
            $storeManagerApiPurchaseOrderFulfillmentData->start_date,
            $storeManagerApiPurchaseOrderFulfillmentData->end_date,
        ];

        /** @var int $locationId */
        $locationId = $storeManagerApiPurchaseOrderFulfillmentData->store_id ?? $storeManagerApiPurchaseOrderFulfillmentData->location_id;

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

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyId(
            $storeManagerApiPurchaseOrderFulfillmentData->purchase_order_id,
            $companyId
        );

        $purchaseOrderCheckRequestService = resolve(PurchaseOrderCheckRequestService::class);
        if (! $purchaseOrderCheckRequestService->canPurchaseOrderDeliveryOrder($purchaseOrder)) {
            abort(417, 'The Delivery Order cannot be accessed.');
        }

        $filterData = [
            'search_text' => $storeManagerApiPurchaseOrderFulfillmentData->search_text,
            'sort_by' => $storeManagerApiPurchaseOrderFulfillmentData->sort_by,
            'sort_direction' => $storeManagerApiPurchaseOrderFulfillmentData->sort_direction,
            'per_page' => $storeManagerApiPurchaseOrderFulfillmentData->per_page,
            'date_range' => $dateRange,
            'location_id' => $locationId,
            'select_status' => $storeManagerApiPurchaseOrderFulfillmentData->status,
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
        PurchaseOrderFulfillmentStoreForStoreManagerData $purchaseOrderFulfillmentStoreForStoreManagerData,
    ): void {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        /** @var int $locationId */
        $locationId = $purchaseOrderFulfillmentStoreForStoreManagerData->store_id ?? $purchaseOrderFulfillmentStoreForStoreManagerData->location_id;

        $storeManagerService = resolve(StoreManagerService::class);

        $storeManagerService->checkAuthorizationForStoreManager($storeManager->id, $locationId);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyId(
            $purchaseOrderFulfillmentStoreForStoreManagerData->purchase_order_id,
            $companyId
        );

        $productIds = collect($purchaseOrderFulfillmentStoreForStoreManagerData->transfer_items)->pluck(
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
            $purchaseOrderFulfillmentStoreForStoreManagerData,
            $products,
            $inventories,
            $batches
        );

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderItems = $purchaseOrderItemQueries->getByPurchaseOrderId(
            $purchaseOrderFulfillmentStoreForStoreManagerData->purchase_order_id,
            $companyId
        );

        /** @var Collection $purchaseOrderItems */
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
                    $purchaseOrderFulfillmentStoreForStoreManagerData->purchase_order_id,
                    $purchaseOrder->status,
                    Statuses::PARTIAL_FULFILLMENT->value,
                    $storeManager
                );

                $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::PARTIAL_FULFILLMENT->value);
            }

            $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

            $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->addNew([
                'purchase_order_id' => $purchaseOrderFulfillmentStoreForStoreManagerData->purchase_order_id,
                'created_by_company_id' => $companyId,
                'happened_at' => $purchaseOrderFulfillmentStoreForStoreManagerData->happened_at,
                'notes' => $purchaseOrderFulfillmentStoreForStoreManagerData->notes,
                'delivery_order_number' => $sequence->getCompleteNumber(),
                'status' => FulfillmentStatuses::DRAFT->value,
            ]);

            $this->addPurchaseOrderFulfillmentItems(
                $purchaseOrderItems,
                $batches,
                $purchaseOrderFulfillmentStoreForStoreManagerData->transfer_items,
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

        if ($purchaseOrderItems->isNotEmpty()) {
            return $purchaseOrderItems;
        }

        abort(412, 'All items that were to be added to the Delivery Order have already been included');
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
