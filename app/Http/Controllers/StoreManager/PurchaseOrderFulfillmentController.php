<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\CommonFunctions;
use App\Domains\Inventory\Services\PurchaseOrderInventoryService;
use App\Domains\Inventory\Services\PurchaseOrderTransitStockService;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PartiallyReceiveFulfillment\Enums\PartiallyReceiveFulfillmentStatuses;
use App\Domains\PartiallyReceiveFulfillment\PartiallyReceiveFulfillmentQueries;
use App\Domains\PartiallyReceiveFulfillment\Resources\PartiallyReceiveFulfillmentResource;
use App\Domains\PartiallyReceiveFulfillment\Services\PartiallyReceiveFulfillmentService;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\Resources\PartiallyReceiveFulfillmentItemResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentData;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Resource\PurchaseDeliveryOrdersListResource;
use App\Domains\PurchaseOrderFulfillment\Resource\PurchaseOrderFulfillmentEditResource;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentCheckRequestService;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentPrintService;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Domains\PurchaseOrderFulfillmentItem\Enums\DiscrepancyTypes;
use App\Domains\PurchaseOrderFulfillmentItem\Export\PurchaseOrderFulfillmentItemExport;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderFulfillmentItem\Resource\FulfillmentItemDeliveryNoteCollectionResource;
use App\Domains\PurchaseOrderFulfillmentItem\Resource\PurchaseOrderFulfillmentItemDiscrepancyResource;
use App\Domains\PurchaseOrderFulfillmentItem\Resource\PurchaseOrderFulfillmentItemResource;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderItem\Resource\PurchaseOrderShippingItemsResource;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;
use App\Models\StoreManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PurchaseOrderFulfillmentController extends Controller
{
    public function __construct(
        protected PurchaseOrderFulfillmentQueries $purchaseOrderFulfillmentQueries
    ) {
    }

    public function deliveryOrder(int $purchaseOrderId): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrder = $purchaseOrderQueries->getByIdLocationAndCompanyIdWithItems(
            $purchaseOrderId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkDeliveryOrder($purchaseOrder);

        return Inertia::render('purchase_order_fulfillments/Index', [
            'purchaseOrder' => $purchaseOrder,
            'hasPurchaseOrderItems' => $purchaseOrderFulfillmentService->hasPurchaseOrderItems($purchaseOrder),
            'staticDetails' => [
                'purchase_order' => OrderTypes::PURCHASE_ORDER->value,
                'sales_order' => OrderTypes::SALES_ORDER->value,
            ],
            'fulFillmentStatuses' => FulfillmentStatuses::getStatuses(),
            'status' => FulfillmentStatuses::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('purchase_order'),
        ]);
    }

    public function fetchPurchaseOrderFulfillments(Request $request): array
    {
        $purchaseOrderId = (int) $request->get('purchase_order_id');
        $companyId = session('store_manager_selected_location_company_id');
        $filterData = $this->prepareFilter($request);

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);

        return $purchaseOrderFulfillmentService->fetchPurchaseOrderFulfillments(
            $purchaseOrderId,
            $companyId,
            $filterData
        );
    }

    public function shippingDetails(int $purchaseOrderId): Response|RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderQueries->getByPurchaseOrderIdAndLocation(
            $purchaseOrderId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $routeUrl = 'store_manager.purchase_orders.index';
        $purchaseOrderFulfillmentService->checkDeliveryOrderDetails($purchaseOrder, $routeUrl);

        return Inertia::render('purchase_order_fulfillments/Manage', [
            'purchaseOrderId' => $purchaseOrderId,
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
            'transferItems' => PurchaseOrderShippingItemsResource::collection($purchaseOrder->items),
        ]);
    }

    public function shipped(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkShippedDeliveryOrder($purchaseOrderFulfillment);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->shippedDeliveryOrder($purchaseOrderFulfillment, $storeManager);
            DB::commit();

            return to_route(
                'store_manager.purchase_order_fulfillments.delivery_order',
                $purchaseOrderFulfillment->purchase_order_id
            )->with('success', 'Shipment updated');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsReceived(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkMarkAsReceivedDeliveryOrder($purchaseOrderFulfillment);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->markAsReceivedDeliveryOrder($purchaseOrderFulfillment, $storeManager);

            DB::commit();

            return to_route(
                'store_manager.purchase_order_fulfillments.delivery_order',
                $purchaseOrderFulfillment->purchaseOrder?->id
            )->with('success', 'Delivery Order details received successfully');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsCancel(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkMarkAsCancelDeliveryOrder($purchaseOrderFulfillment);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->markAsCancelDeliveryOrder($purchaseOrderFulfillment, $storeManager);

            DB::commit();

            return to_route(
                'store_manager.purchase_order_fulfillments.delivery_order',
                $purchaseOrderFulfillment->purchaseOrder?->id
            )->with('success', 'Delivery Order canceled successfully');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsOpen(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderFulfillment->status !== FulfillmentStatuses::DRAFT->value) {
            throw new RedirectBackWithErrorException(
                'At this moment, opening the delivery order is not possible as it currently does not have a draft status.'
            );
        }

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->markAsOpen($purchaseOrderFulfillment, $storeManager);

            DB::commit();

            return to_route(
                'store_manager.purchase_order_fulfillments.delivery_order',
                $purchaseOrderFulfillment->purchaseOrder?->id
            )->with('success', 'Delivery Order Open successfully');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function deliveryNote(int $purchaseOrderFulfillmentId): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrder = $purchaseOrderQueries->getByPurchaseOrderFulfillmentIdAndLocation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getById($purchaseOrderFulfillmentId);

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkDeliveryNote(
            $purchaseOrderFulfillment,
            'store_manager.purchase_orders.index'
        );

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItemQueries->getByPurchaseOrderFulfillmentId(
            $purchaseOrderFulfillmentId,
            $companyId
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $externalLocationProductStocks = $purchaseOrderService->getExternalLocationStocks(
            $purchaseOrder->external_location_id,
            $purchaseOrder->items->pluck('product.upc')->filter()->unique()->toArray()
        );

        return Inertia::render('purchase_order_fulfillments/DeliveryNote', [
            'purchaseOrderFulfillmentId' => $purchaseOrderFulfillmentId,
            'purchaseOrderProductIds' => $purchaseOrder->items->pluck('product_id'),
            'transferItems' => new FulfillmentItemDeliveryNoteCollectionResource(
                $purchaseOrderFulfillmentItems,
                $externalLocationProductStocks
            ),
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
            'statuses' => [
                'discrepancy' => FulfillmentStatuses::DISCREPANCY->value,
            ],
            'discrepancyTypes' => [
                'positive' => DiscrepancyTypes::POSITIVE->value,
                'negative' => DiscrepancyTypes::NEGATIVE->value,
            ],
            'purchaseOrderId' => $purchaseOrder->id,
            'locationId' => $purchaseOrder->location_id,
            'externalLocationId' => $purchaseOrder->external_location_id,
            'partiallyReceiveStatuses' => PartiallyReceiveFulfillmentStatuses::getStatuses(),
        ]);
    }

    public function discrepancyProof(Request $request, int $purchaseOrderFulfillmentItemId): RedirectResponse
    {
        $validatedData = $request->validate([
            'discrepancy_proof' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                'max:' . config('services.max_upload_size'),
            ],
        ]);

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        $purchaseOrderFulfillmentItemQueries->uploadDiscrepancyProof($validatedData, $purchaseOrderFulfillmentItemId);

        return back()->with('success', 'The discrepancy proof has been uploaded successfully.');
    }

    public function removeDiscrepancyProof(int $purchaseOrderFulfillmentItemId): RedirectResponse
    {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        $purchaseOrderFulfillmentItemQueries->removeDiscrepancyProof($purchaseOrderFulfillmentItemId);

        return back()->with('success', 'Discrepancy proof removed successfully.');
    }

    public function closed(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkClosedDeliveryOrder($purchaseOrderFulfillment);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdWithItemsAndFulfillment(
            $purchaseOrderFulfillment->purchase_order_id,
            $companyId
        );

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
            $purchaseOrderFulfillmentService->closedDeliveryOrder(
                $purchaseOrderFulfillment,
                $purchaseOrder,
                $storeManager
            );

            DB::commit();

            return to_route('store_manager.purchase_order_fulfillments.delivery_order', $purchaseOrder->id)->with(
                'success',
                'Delivery Order is closed.'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function discrepancy(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkDeliveryOrderDiscrepancy($purchaseOrderFulfillment);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->deliveryOrderDiscrepancy($purchaseOrderFulfillment, $storeManager);

            DB::commit();

            return to_route(
                'store_manager.purchase_order_fulfillments.delivery_order',
                $purchaseOrderFulfillment->purchaseOrder?->id
            )->with('success', 'Successfully Generated Delivery Order Discrepancy');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function discrepancyClosedDeliveryOrder(int $purchaseOrderFulfillmentId): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationAndCompanyId(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderFulfillment->status !== FulfillmentStatuses::DISCREPANCY->value) {
            throw new RedirectBackWithErrorException('Transfer should be discrepancy to edit the records.');
        }

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItemQueries->getByPurchaseOrderFulfillmentId(
            $purchaseOrderFulfillmentId,
            $companyId
        );

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrder = $purchaseOrderQueries->getByPurchaseOrderFulfillmentId(
            $purchaseOrderFulfillmentId,
            $companyId
        );

        return Inertia::render('purchase_order_fulfillments/Discrepancy', [
            'transferItems' => PurchaseOrderFulfillmentItemDiscrepancyResource::collection(
                $purchaseOrderFulfillmentItems
            ),
            'purchaseOrderFulfillmentId' => $purchaseOrderFulfillmentId,
            'discrepancyTypes' => [
                'positive' => DiscrepancyTypes::POSITIVE->value,
                'negative' => DiscrepancyTypes::NEGATIVE->value,
            ],
            'purchaseOrderId' => $purchaseOrder->id,
        ]);
    }

    public function setReceivedQuantitySameAsQuantity(int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        $purchaseOrderFulfillmentItemQueries->setReceivedQuantitySameAsQuantity(
            $purchaseOrderFulfillmentId,
            session('store_manager_selected_location_company_id')
        );

        return back()->with('success', 'DO updated.');
    }

    public function updateReceivedQuantities(Request $request, int $purchaseOrderFulfillmentId): void
    {
        $validatedData = $request->validate([
            'item_id' => ['required', 'integer'],
            'received_quantity' => ['required', 'numeric'],
            'status' => ['nullable', 'integer'],
        ]);

        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
        $purchaseOrderFulfillmentItem = $purchaseOrderFulfillmentItemQueries->getByIdWithProductAndPurchaseOrder(
            $validatedData['item_id']
        );

        /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        $derivative = $purchaseOrderItem->derivative;

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $externalLocationProductStocks = $purchaseOrderService->getExternalLocationStocks(
            $purchaseOrder->external_location_id,
            [$product->upc]
        );

        $externalLocationStock = (float) $externalLocationProductStocks->firstWhere(
            'upc',
            $product->upc
        )['external_stock'];

        $quantity = $validatedData['received_quantity'];
        if ($derivative) {
            $quantity = CommonFunctions::numberFormat($quantity / $derivative->ratio);
        }

        if ($quantity > $externalLocationStock) {
            abort(417, 'Quantity exceeds available external stock. Please enter a lower quantity.');
        }

        DB::beginTransaction();
        try {
            $purchaseOrderFulfillmentItemQueries->updateReceivedQuantityAndDiscrepancyStatusById(
                $validatedData,
                $purchaseOrderFulfillmentId,
                $companyId
            );

            $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdAndCompanyId(
                $purchaseOrderFulfillmentId,
                $companyId
            );

            if ($purchaseOrderFulfillment->getStatus() === FulfillmentStatuses::RECEIVED->value) {
                $purchaseOrderFulfillmentItemQueries->removeDiscrepancyProof($validatedData['item_id']);
            }

            DB::commit();
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Vendor Create');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $purchaseOrderFulfillmentId): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelationForEdit(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->checkEditDeliveryOrder($purchaseOrderFulfillment);

        $packageTypeQueries = resolve(PackageTypeQueries::class);

        return Inertia::render('purchase_order_fulfillments/Manage', [
            'purchaseOrderId' => $purchaseOrderFulfillment->purchase_order_id,
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
            'purchaseOrderFulfillment' => new PurchaseOrderFulfillmentEditResource($purchaseOrderFulfillment),
        ]);
    }

    public function update(
        PurchaseOrderFulfillmentData $purchaseOrderFulfillmentData,
        int $purchaseOrderFulfillmentId
    ): RedirectResponse {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationAndCompanyIdWithItems(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderFulfillment->status !== FulfillmentStatuses::DRAFT->value) {
            throw new RedirectWithErrorException(
                'store_manager.purchase_orders.index',
                'As the status is not set to draft, you do not have the rights to update it.'
            );
        }

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $productIds = collect($purchaseOrderFulfillmentData->transfer_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        [$products, $batches, $inventories, $derivatives] = $purchaseOrderFulfillmentService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $purchaseOrder->location_id,
        );

        $purchaseOrderFulfillmentCheckRequestService = resolve(PurchaseOrderFulfillmentCheckRequestService::class);
        $purchaseOrderFulfillmentCheckRequestService->checkRequestDetails(
            $purchaseOrderFulfillmentData,
            $products,
            $inventories,
            $batches,
            $derivatives,
            $purchaseOrder->items,
        );

        $purchaseOrderItemIds = collect($purchaseOrderFulfillmentData->transfer_items)->pluck(
            'purchase_order_item_id'
        )->unique()->filter()->toArray();

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderItems = $purchaseOrderItemQueries->getByIds($purchaseOrderItemIds);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->updatePurchaseOrderFulfillmentData(
                $purchaseOrderFulfillment,
                $purchaseOrderFulfillmentData,
                $purchaseOrderItems,
                $batches
            );

            DB::commit();

            return to_route('store_manager.purchase_order_fulfillments.delivery_order', $purchaseOrder->id)->with(
                'success',
                'DO updated successfully'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function addShippingDetails(
        PurchaseOrderFulfillmentData $purchaseOrderFulfillmentData,
        int $purchaseOrderId
    ): RedirectResponse {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdLocationAndCompanyIdWithItems(
            $purchaseOrderId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $routeUrl = 'store_manager.purchase_orders.index';
        $purchaseOrderFulfillmentService->checkAllItemsDelivered($purchaseOrder->items, $routeUrl);

        $productIds = collect($purchaseOrderFulfillmentData->transfer_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        [$products, $batches, $inventories, $derivatives] = $purchaseOrderFulfillmentService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $purchaseOrder->location_id,
        );

        $purchaseOrderFulfillmentCheckRequestService = resolve(PurchaseOrderFulfillmentCheckRequestService::class);
        $purchaseOrderFulfillmentCheckRequestService->checkRequestDetails(
            $purchaseOrderFulfillmentData,
            $products,
            $inventories,
            $batches,
            $derivatives,
            $purchaseOrder->items
        );

        DB::beginTransaction();

        try {
            $PurchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
            $PurchaseOrderFulfillmentService->addShippingDetails(
                $purchaseOrderFulfillmentData,
                $purchaseOrder,
                $batches
            );

            DB::commit();

            return to_route('store_manager.purchase_order_fulfillments.delivery_order', $purchaseOrderId)->with(
                'success',
                'DO created successfully'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function fetchPurchaseOrderFulfillmentItemById(int $purchaseOrderFulfillmentId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItemQueries->getByPurchaseOrderFulfillmentIdAndLocation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        return [
            'purchase_order_fulfillment_items' => PurchaseOrderFulfillmentItemResource::collection(
                $purchaseOrderFulfillmentItems
            ),
            'totals' => [
                'transfer' => $purchaseOrderFulfillmentItems->sum('transfer_quantity'),
                'received' => $purchaseOrderFulfillmentItems->sum('received_quantity'),
            ],
        ];
    }

    public function exportPurchaseOrderFulfillmentItems(
        int $purchaseOrderFulfillmentId,
        string $fileName
    ): BinaryFileResponse {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItemQueries->getByPurchaseOrderFulfillmentIdAndLocation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        return Excel::download(new PurchaseOrderFulfillmentItemExport($purchaseOrderFulfillmentItems), $fileName);
    }

    public function purchaseOrderDeliveryNoteItemRemarks(Request $request, int $purchaseOrderFulfillmentItemId): void
    {
        $validatedData = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        $purchaseOrderFulfillmentItemQueries->deliveryNoteItemRemarks(
            $storeManager,
            $validatedData['remarks'],
            $purchaseOrderFulfillmentItemId
        );
    }

    public function updateAdditionalItems(Request $request, int $purchaseOrderFulfillmentId): void
    {
        $validatedData = $request->validate([
            'additional_items' => ['required', 'array'],
            'additional_items.*.purchase_order_fulfillment_id' => ['required', 'integer'],
            'additional_items.*.product_id' => ['required', 'integer'],
            'additional_items.*.has_batch' => ['required', 'boolean'],
            'additional_items.*.unit_of_measure_derivative_id' => ['nullable', 'numeric', 'min:0'],
            'additional_items.*.package_type_id' => ['nullable', 'integer'],
            'additional_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'additional_items.*.received_quantity' => ['required', 'numeric', 'min:0.01'],
            'additional_items.*.package_quantity' => ['nullable', 'numeric', 'min:0'],
            'additional_items.*.package_total_quantity' => ['nullable', 'numeric', 'min:0'],
            'additional_items.*.remarks' => ['nullable', 'string'],
            'additional_items.*.batch_details' => ['nullable', 'array'],
            'additional_items.*.batch_details.*.batch_number' => ['nullable', 'string'],
            'additional_items.*.batch_details.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $companyId = session('store_manager_selected_location_company_id');
        $requestData = $request->all();

        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelationForEdit(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderFulfillment->status !== FulfillmentStatuses::RECEIVED->value) {
            throw new RedirectBackWithErrorException('Status should be Shipped to updates the records.');
        }

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        /** @var array $additionalItems */
        $additionalItems = $validatedData['additional_items'];
        $productIds = collect($additionalItems)->pluck('product_id')->unique()->filter()->toArray();
        [$products, $batches, $inventories, $derivatives] = $purchaseOrderFulfillmentService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $purchaseOrder->location_id,
        );

        $purchaseOrderFulfillmentCheckRequestService = resolve(PurchaseOrderFulfillmentCheckRequestService::class);
        $purchaseOrderFulfillmentCheckRequestService->checkAdditionalItemsRequest(
            $requestData,
            $products,
            $batches,
            $purchaseOrderFulfillment
        );

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->updateAdditionalItems(
                $additionalItems,
                $purchaseOrderFulfillment,
                $batches,
                $products,
            );

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Purchase order Additional item received', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function removeAdditionalItem(int $purchaseOrderFulfillmentItemId): void
    {
        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
            $purchaseOrderFulfillmentItemQueries->removeAdditionalItemAndRelations($purchaseOrderFulfillmentItemId);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Admin Additional item remove', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function closeDiscrepancy(Request $request, int $purchaseOrderFulfillmentId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);

        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderFulfillment->getStatus() !== FulfillmentStatuses::DISCREPANCY->value) {
            throw new RedirectWithErrorException(
                'store_manager.purchase_orders.index',
                'Status should indicate a discrepancy in order to close the Delivery Order'
            );
        }

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $anyReceivedQuantityPending = $purchaseOrderFulfillment->getItems()->contains(
            fn ($item): bool => null === $item->received_quantity
        );

        if ($anyReceivedQuantityPending) {
            throw new RedirectBackWithErrorException('one of the Delivery OrderList item received quantity pending.');
        }

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->loadEmployee($storeManager);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService->closeDeliveryOrderDiscrepancy($purchaseOrderFulfillment, $storeManager);

            DB::commit();

            return to_route('store_manager.purchase_order_fulfillments.delivery_order', $purchaseOrder->id)->with(
                'success',
                'Delivery Order closed Successfully'
            );
        } catch (Throwable $throwable) {
            Log::error('Admin-Purchase-Order-Close-Discrepancy', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function print(int $purchaseOrderFulfillmentId): string
    {
        $purchaseOrderFulfillmentPrintService = resolve(PurchaseOrderFulfillmentPrintService::class);

        return $purchaseOrderFulfillmentPrintService->print(
            $purchaseOrderFulfillmentId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
        );
    }

    public function printBoxSticker(int $purchaseOrderFulfillmentId, string $transferItemIds): string
    {
        $itemIds = explode(',', $transferItemIds);

        $purchaseOrderFulfillmentPrintService = resolve(PurchaseOrderFulfillmentPrintService::class);

        return $purchaseOrderFulfillmentPrintService->printSticker(
            $purchaseOrderFulfillmentId,
            session('store_manager_selected_location_company_id'),
            $itemIds,
            session('store_manager_selected_location_id'),
        );
    }

    public function deliveryOrders(Request $request): Response
    {
        $deliveryOrderNumber = $request->get('order_number');

        return Inertia::render('purchase_delivery_orders/Index', [
            'staticDetails' => [
                'purchase_order' => OrderTypes::PURCHASE_ORDER->value,
                'sales_order' => OrderTypes::SALES_ORDER->value,
            ],
            'fulFillmentStatuses' => FulfillmentStatuses::getStatuses(),
            'status' => FulfillmentStatuses::getList(),
            'orderType' => OrderTypes::formattedOrderForSelection(),
            'exportPermission' => PermissionList::getExportPermissionName('purchase_order'),
            'purchaseOrdersFilterData' => [
                'select_status' => (int) $request->get('select_status') > 0 ? (int) $request->get(
                    'select_status'
                ) : null,
                'select_order_type' => (int) $request->get('select_order_type') > 0 ? (int) $request->get(
                    'select_order_type'
                ) : null,
            ],
            'orderNumber' => $deliveryOrderNumber ?? null,
        ]);
    }

    public function fetchPurchaseDeliveryOrders(Request $request): array
    {
        $companyId = session('store_manager_selected_location_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'select_status' => $request->get('select_status'),
            'select_order_type' => $request->get('select_order_type'),
            'date_range' => $request->get('date_range'),
            'location_id' => session('store_manager_selected_location_id'),
        ];

        $lengthAwarePaginator = $this->purchaseOrderFulfillmentQueries->deliveryOrderListQuery(
            $filterData,
            $companyId
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PurchaseDeliveryOrdersListResource::collection($lengthAwarePaginator->getCollection()),
            'statusCounts' => $purchaseOrderFulfillmentService->getDeliveryOrderStatusCounts($filterData, $companyId),
        ];
    }

    public function partialReceive(int $purchaseOrderFulfillmentId, Request $request): void
    {
        $companyId = session('store_manager_selected_location_company_id');
        /** @var User $user */
        $user = $request->user();

        $purchaseOrderFulfillment = $this->purchaseOrderFulfillmentQueries->getByIdLocationWithRelation(
            $purchaseOrderFulfillmentId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        DB::beginTransaction();

        try {
            $partiallyReceiveFulfillmentService = resolve(PartiallyReceiveFulfillmentService::class);
            $partiallyReceiveFulfillmentService->addPartialReceive(
                $user,
                $purchaseOrderFulfillmentId,
                (int) $purchaseOrderFulfillment->purchaseOrder?->location_id,
                (array) $request->partial_items
            );

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Partial Receive', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function fetchPartiallyReceiveFulfillment(int $purchaseOrderFulfillmentId): array
    {
        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
        $partialReceiveFulfillment = $partiallyReceiveFulfillmentQueries->getPartiallyReceiveFulfillments(
            $purchaseOrderFulfillmentId
        );

        return [
            'partially_receive_fulfillments' => PartiallyReceiveFulfillmentResource::collection(
                $partialReceiveFulfillment
            ),
        ];
    }

    public function fetchPartiallyReceiveFulfillmentItems(int $partialReceiveId): array
    {
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        $partialReceiveFulfillmentItem = $partiallyReceiveFulfillmentItemQueries->getPartiallyReceiveFulfillmentItemsWithTrashed(
            $partialReceiveId
        );

        return [
            'partial_receive_fulfillment_items' => PartiallyReceiveFulfillmentItemResource::collection(
                $partialReceiveFulfillmentItem
            ),
        ];
    }

    public function prepareFilter(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'select_status' => $request->get('select_status'),
            'date_range' => $request->get('date_range'),
        ];
    }

    public function updateBatchDetails(Request $request, int $purchaseOrderFulfillmentItemId): void
    {
        $companyId = session('store_manager_selected_location_company_id');
        /* @phpstan-ignore-next-line */
        $batchDetails = collect($request->get('batch_details'));
        $discrepancyStatus = $request->get('discrepancy_status');

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->updateTheBatchDetails(
            $batchDetails,
            $purchaseOrderFulfillmentItemId,
            $discrepancyStatus,
            $companyId
        );
    }

    public function updateDiscrepancyBatchDetails(Request $request, int $purchaseOrderFulfillmentItemId): void
    {
        $companyId = session('store_manager_selected_location_company_id');
        /* @phpstan-ignore-next-line */
        $batchDetails = collect($request->get('batch_details'));
        $discrepancyStatus = $request->get('discrepancy_status');

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->updateTheDiscrepancyBatchDetails(
            $batchDetails,
            $purchaseOrderFulfillmentItemId,
            $discrepancyStatus,
            $companyId
        );
    }

    public function deleteBatchDetails(Request $request, int $purchaseOrderFulfillmentItemId): void
    {
        $batchNumber = $request->get('batch_number');

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
        $purchaseOrderFulfillmentService->deleteBatchDetails($purchaseOrderFulfillmentItemId, $batchNumber);
    }

    public function partialReceiveApproved(int $partialReceiveId): void
    {
        DB::beginTransaction();
        try {
            $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
            $partiallyReceiveFulfillmentQueries->updateStatus(
                $partialReceiveId,
                PartiallyReceiveFulfillmentStatuses::APPROVED->value
            );
            DB::commit();
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Vendor Create');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function partialReceiveCompleted(Request $request, int $partialReceiveId): void
    {
        /** @var User $user */
        $user = $request->user();

        DB::beginTransaction();
        try {
            $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
            $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
            $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
            $purchaseOrderTransitStockService = resolve(PurchaseOrderTransitStockService::class);

            $partiallyReceiveFulfillment = $partiallyReceiveFulfillmentQueries->updateStatusWithRecord(
                $partialReceiveId,
                PartiallyReceiveFulfillmentStatuses::COMPLETED->value
            );

            $companyId = session('store_manager_selected_location_company_id');
            $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdWithRelation(
                $partiallyReceiveFulfillment->purchase_order_fulfillment_id,
                $companyId
            );

            $purchaseOrderInventoryService->updateInventoryToPartialReceiver(
                $purchaseOrderFulfillment,
                $user,
                $partiallyReceiveFulfillment
            );

            $purchaseOrderTransitStockService->removePartialCompletedTransitStock(
                $purchaseOrderFulfillment,
                $partialReceiveId
            );

            DB::commit();
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Vendor Create');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    public function partialReceiveCancelled(int $partialReceiveId): void
    {
        DB::beginTransaction();
        try {
            $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
            $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
            $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
            $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);

            $partiallyReceiveFulfillment = $partiallyReceiveFulfillmentQueries->updateStatusWithRecord(
                $partialReceiveId,
                PartiallyReceiveFulfillmentStatuses::CANCELLED->value
            );

            $companyId = session('store_manager_selected_location_company_id');
            $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdWithRelation(
                $partiallyReceiveFulfillment->purchase_order_fulfillment_id,
                $companyId
            );

            $purchaseOrderFulfillmentItemQueries->minusReceivedQuantity($purchaseOrderFulfillment, $partialReceiveId);

            $partiallyReceiveFulfillmentItemQueries->deleteReceivedQuantity(
                $purchaseOrderFulfillment,
                $partialReceiveId
            );

            DB::commit();
        } catch (Throwable $throwable) {
            CommonFunctions::logErrorDetails($throwable, 'Admin Vendor Create');

            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }
}
