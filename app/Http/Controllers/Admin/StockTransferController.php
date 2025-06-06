<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Media\StockTransfer\MimeTypes;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sequence\SequenceQueries;
use App\Domains\StockTransfer\DataObjects\StockTransferData;
use App\Domains\StockTransfer\DataObjects\StockTransferRequestOrderData;
use App\Domains\StockTransfer\DataObjects\StockTransferShippedData;
use App\Domains\StockTransfer\DataObjects\StockTransferUpdateStatusData;
use App\Domains\StockTransfer\Enums\ShippedTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypes;
use App\Domains\StockTransfer\Exports\StockTransferExport;
use App\Domains\StockTransfer\Resources\StockTransferEditResource;
use App\Domains\StockTransfer\Resources\StockTransferItemDiscrepancyResource;
use App\Domains\StockTransfer\Resources\StockTransferListResource;
use App\Domains\StockTransfer\Resources\StockTransferOrderEditResource;
use App\Domains\StockTransfer\Resources\StockTransferRequestOrderEditResource;
use App\Domains\StockTransfer\Resources\StockTransferShipResource;
use App\Domains\StockTransfer\Services\StockTransferCheckRequestService;
use App\Domains\StockTransfer\Services\StockTransferPrintService;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferItem\Enums\StockTransferDiscrepancyTypes;
use App\Domains\StockTransferItem\Exports\StockTransferItemsExport;
use App\Domains\StockTransferItem\Resources\StockTransferItemDeliveryNoteResource;
use App\Domains\StockTransferItem\Resources\StockTransferItemsListResource;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\StockTransferReason\StockTransferReasonQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockTransferQueries $stockTransferQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $stockTransferService = resolve(StockTransferService::class);
        [$stores, $warehouses] = $stockTransferService->getStoresAndWarehouses($companyId);

        $locationId = (int) $request->get('location_id');

        $stockTransferNumber = $request->get('stock_transfer_number');
        $stockTransferId = $request->get('stock_transfer_id');

        return Inertia::render('stock_transfers/Index', [
            'statuses' => StatusTypes::getStatuses(),
            'status' => StatusTypes::formattedForSelection(),
            'staticTitleStatuses' => StatusTypes::getTitleStatuses(),
            'stores' => $stores,
            'warehouses' => $warehouses,
            'stockTransferTypes' => StockTransferTypes::getTransferNames(),
            'transferTypes' => StockTransferTypes::formattedForSelection(),
            'allTransferTypes' => TransferTypes::formattedForSelection(),
            'stockTransferNumber' => $stockTransferNumber > 0 ? $stockTransferNumber : null,
            'stockTransferId' => $stockTransferId > 0 ? $stockTransferId : null,
            'staticStockTransferType' => StockTransferTypes::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('stock_transfer'),
            'mimeTypes' => MimeTypes::getFormattedArrayForStaticUse(),
            'dashboardFilterData' => [
                'transfer_type' => (int) $request->get('transfer_type') > 0 ? (int) $request->get(
                    'transfer_type'
                ) : null,
                'is_from_stock_overview' => (bool) $request->get('is_from_stock_overview'),
                'select_status' => (int) $request->get('select_status') > 0 ? (int) $request->get(
                    'select_status'
                ) : null,
                'location_id' => $locationId,
            ],
            'shippedTypes' => ShippedTypes::formattedForSelection(),
            'shippedTransit' => ShippedTypes::TRANSIT->value,
            'shippedDirect' => ShippedTypes::DIRECT->value,
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'allowSmartTransfer' => config('app.allow_smart_transfer'),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>|AnonymousResourceCollection|int>
     */
    public function fetchStockTransfers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'stock_transfer_date' => $request->get('stock_transfer_date'),
            'location_id' => $request->get('location_id'),
            'select_status' => $request->get('select_status'),
            'transfer_type' => $request->get('transfer_type'),
            'stock_transfer_id' => null,
            'dashboard_transfer_type' => $request->get('dashboard_transfer_type'),
        ];

        if (null !== $filterData['transfer_type'] && null !== $filterData['dashboard_transfer_type']) {
            $filterData['dashboard_transfer_type'] = null;
        }

        if (null !== $request->get('stock_transfer_number')) {
            $filterData['search_text'] = $request->get('stock_transfer_number');
        }

        if (null !== $request->get('stock_transfer_id')) {
            $filterData['stock_transfer_id'] = $request->get('stock_transfer_id');
        }

        $lengthAwarePaginator = $this->stockTransferQueries->listQuery($filterData, session('admin_company_id'));

        $transferOrderCounts = [];
        $requestOrderCounts = [];

        $transferOrderStatusCounts = $this->stockTransferQueries->transferOrderStatusCount(
            StockTransferTypes::TRANSFER_ORDER->value,
            $filterData,
            session('admin_company_id')
        );

        $requestOrderStatusCounts = $this->stockTransferQueries->requestOrderStatusCount(
            StockTransferTypes::REQUEST_ORDER->value,
            $filterData,
            session('admin_company_id')
        );

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferOrderCounts[$statusName] = [
                'count' => $transferOrderStatusCount->count,
                'id' => $transferOrderStatusCount->status,
            ];
        }

        foreach ($requestOrderStatusCounts as $requestOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($requestOrderStatusCount->status);
            $requestOrderCounts[$statusName] = [
                'count' => $requestOrderStatusCount->count,
                'id' => $requestOrderStatusCount->status,
            ];
        }

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StockTransferListResource::collection($lengthAwarePaginator->getCollection()),
            'transferOrderStatusCounts' => $transferOrderCounts,
            'requestOrderStatusCounts' => $requestOrderCounts,
        ];
    }

    public function exportStockTransferItems(int $stockTransferId, string $fileName): BinaryFileResponse
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItems = $stockTransferItemQueries->getByStockTransferId(
            $stockTransferId,
            session('admin_company_id')
        );

        return Excel::download(new StockTransferItemsExport($stockTransferItems), $fileName);
    }

    public function create(string $transferType): Response
    {
        $companyId = session('admin_company_id');
        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferReasonQueries = resolve(StockTransferReasonQueries::class);

        [$stores, $warehouses] = $stockTransferService->getStoresAndWarehouses($companyId);

        $stockTransferReasons = $stockTransferReasonQueries->getStockTransferReasons($companyId);
        $transferType = Str::lower(str_replace(' ', '_', $transferType));

        if (StockTransferTypes::getCaseName(StockTransferTypes::TRANSFER_ORDER->value) === $transferType) {
            $packageTypeQueries = resolve(PackageTypeQueries::class);

            return Inertia::render('stock_transfers/TransferOrderForm', [
                'stores' => $stores,
                'warehouses' => $warehouses,
                'transferType' => $transferType,
                'stockTransferReasons' => $stockTransferReasons,
                'stockTransferTypes' => StockTransferTypes::getTransferNames(),
                'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
                'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
                'locationTypes' => LocationTypes::getList(),
            ]);
        }

        return Inertia::render('stock_transfers/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'transferType' => $transferType,
            'stockTransferReasons' => $stockTransferReasons,
            'stockTransferTypes' => StockTransferTypes::getTransferNames(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getStockTransferTypes(): array
    {
        return [
            'types' => StockTransferTypes::formattedForSelection(),
        ];
    }

    public function store(StockTransferData $stockTransferData, Request $request): RedirectResponse
    {
        $sequence = null;
        $companyId = session('admin_company_id');
        $stockTransferService = resolve(StockTransferService::class);
        $sequenceQueries = resolve(SequenceQueries::class);
        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
        $productIds = collect($stockTransferData->transfer_items)->pluck('product_id')->unique()->filter()->toArray();

        [$products, $batches, $inventories, $derivatives] = $stockTransferService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $stockTransferData->source_location_id,
        );

        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            $products,
            $inventories,
            $batches,
            $derivatives
        );

        /** @var Admin $admin */
        $admin = $request->user();

        [$transferType, $locationId] = $stockTransferService->prepareLocationIdAndTransferType($stockTransferData);

        DB::beginTransaction();

        try {
            if (
                StockTransferTypes::getCaseName(
                    StockTransferTypes::TRANSFER_ORDER->value
                ) === $stockTransferData->transfer_type
            ) {
                $sequence = $sequenceQueries->addNew($stockTransferData->source_location_id, $transferType);
            }

            if (
                StockTransferTypes::getCaseName(
                    StockTransferTypes::REQUEST_ORDER->value
                ) === $stockTransferData->transfer_type
            ) {
                $sequence = $sequenceQueries->addNew($stockTransferData->destination_location_id, $transferType);
            }

            $stockTransferDetails = $stockTransferService->prepareStockTransferDetails(
                $stockTransferData,
                $companyId,
                $admin,
                $transferType,
                $sequence
            );

            $stockTransfer = $this->stockTransferQueries->addNew($stockTransferDetails);

            if ($transferType === StockTransferTypes::REQUEST_ORDER->value) {
                $stockTransferService->saveStockTransferItems(
                    $stockTransferData,
                    $stockTransfer->getKey(),
                    $admin,
                    StatusTypes::DRAFT->value,
                    $derivatives
                );
            }

            if ($transferType === StockTransferTypes::TRANSFER_ORDER->value) {
                $stockTransferService->saveStockTransferItemAndBatchRecords(
                    $stockTransferData,
                    $stockTransfer->getKey(),
                    $products,
                    $companyId,
                    $admin,
                    StatusTypes::DRAFT->value,
                    $derivatives
                );
            }

            $stockTransfer = $this->stockTransferQueries->loadItemsAndBatches($stockTransfer);

            $stockTransferService->reserveStockTransferItemStocks($products, $inventories, $stockTransfer);

            DB::commit();

            return to_route('admin.stock_transfers.index')
                ->with('success', 'Stock transfer added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Stock Transfer-Store', [
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

    public function edit(int $stockTransferId): Response
    {
        $companyId = session('admin_company_id');
        $stockTransfer = $this->stockTransferQueries->getByIdWithItems($stockTransferId, $companyId);

        if (
            $stockTransfer->status !== StatusTypes::DRAFT->value
            && $stockTransfer->status !== StatusTypes::SYSTEM_GENERATED->value
        ) {
            throw new RedirectBackWithErrorException('Stock Transfer should be draft to edit the records.');
        }

        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferReasonQueries = resolve(StockTransferReasonQueries::class);

        [$stores, $warehouses] = $stockTransferService->getStoresAndWarehouses($companyId);

        [$sourceInventories, $destinationInventories] = $stockTransferService->getStocks($stockTransfer);

        $stockTransfer['source_inventories'] = $sourceInventories;
        $stockTransfer['destination_inventories'] = $destinationInventories;

        $stockTransferReasons = $stockTransferReasonQueries->getStockTransferReasons($companyId);

        if ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value) {
            $stockTransfer = $this->stockTransferQueries->loadItemsBatchesAndProduct($stockTransfer);
            $packageTypeQueries = resolve(PackageTypeQueries::class);

            return Inertia::render('stock_transfers/TransferOrderForm', [
                'stores' => $stores,
                'warehouses' => $warehouses,
                'stockTransfer' => new StockTransferOrderEditResource($stockTransfer),
                'stockTransferReasons' => $stockTransferReasons,
                'stockTransferTypes' => StockTransferTypes::getTransferNames(),
                'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
                'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
                'locationTypes' => LocationTypes::getList(),
            ]);
        }

        return Inertia::render('stock_transfers/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'stockTransfer' => new StockTransferEditResource($stockTransfer),
            'stockTransferReasons' => $stockTransferReasons,
            'stockTransferTypes' => StockTransferTypes::getTransferNames(),
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    public function update(
        Request $request,
        StockTransferData $stockTransferData,
        int $stockTransferId
    ): RedirectResponse {
        $companyId = session('admin_company_id');
        $stockTransfer = $this->stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);

        if (
            $stockTransfer->status !== StatusTypes::DRAFT->value
            && $stockTransfer->status !== StatusTypes::SYSTEM_GENERATED->value
        ) {
            throw new RedirectBackWithErrorException('Stock Transfer should be draft to updates the records.');
        }

        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
        $stockTransferCheckRequestService->locationChanged($stockTransfer, $stockTransferData);

        $stockTransferService = resolve(StockTransferService::class);
        $productIds = collect($stockTransferData->transfer_items)->pluck('product_id')->unique()->filter()->toArray();

        [$products, $batches, $inventories, $derivatives] = $stockTransferService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $stockTransferData->source_location_id,
        );

        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferData,
            $products,
            $inventories,
            $batches,
            $derivatives
        );

        /** @var Admin $admin */
        $admin = $request->user();

        DB::beginTransaction();

        try {
            $stockTransferDetails = $stockTransferService->prepareStockTransferDetailsForUpdate($stockTransferData);

            $stockTransfer = $this->stockTransferQueries->update($stockTransferDetails, $stockTransferId, $companyId);
            $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
            $stockTransferItemQueries->deleteItemAndBatches($stockTransfer);

            if ($stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value) {
                $stockTransferService->saveStockTransferItems(
                    $stockTransferData,
                    $stockTransfer->getKey(),
                    $admin,
                    StatusTypes::DRAFT->value,
                    $derivatives
                );
            }

            if ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value) {
                $stockTransferService->saveStockTransferItemAndBatchRecords(
                    $stockTransferData,
                    $stockTransfer->getKey(),
                    $products,
                    $companyId,
                    $admin,
                    StatusTypes::DRAFT->value,
                    $derivatives
                );
            }

            $stockTransfer = $this->stockTransferQueries->loadItemsAndBatches($stockTransfer);

            $stockTransferService->reserveStockTransferItemStocks($products, $inventories, $stockTransfer);

            DB::commit();

            return to_route('admin.stock_transfers.index')
                ->with('success', 'The stock transfer has been updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Stock Transfer Update', [
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

    public function updateStatus(
        Request $request,
        StockTransferUpdateStatusData $stockTransferUpdateStatusData,
        int $stockTransferId
    ): RedirectResponse {
        $companyId = session('admin_company_id');
        $message = 'Status changed successfully.';
        /** @var Admin $user */
        $user = $request->user();
        $storeTransferService = resolve(StockTransferService::class);

        if ($stockTransferUpdateStatusData->status_id === StatusTypes::OPEN->value) {
            $storeTransferService->markAsOpen(
                $stockTransferId,
                $companyId,
                $stockTransferUpdateStatusData->status_id,
                $user
            );
        }

        if ($stockTransferUpdateStatusData->status_id === StatusTypes::TRANSIT_IN->value) {
            $storeTransferService->markAsTransitIn(
                $stockTransferId,
                $companyId,
                $stockTransferUpdateStatusData->status_id,
                $user
            );
        }

        if ($stockTransferUpdateStatusData->status_id === StatusTypes::TRANSIT_OUT->value) {
            $storeTransferService->markAsTransitOut(
                $stockTransferId,
                $companyId,
                $stockTransferUpdateStatusData->status_id,
                $user
            );
        }

        if ($stockTransferUpdateStatusData->status_id === StatusTypes::DISCREPANCY->value) {
            $storeTransferService->markAsDiscrepancy(
                $stockTransferId,
                $companyId,
                $stockTransferUpdateStatusData->status_id,
                $user
            );

            $message = 'There is a discrepancy in the stock transfer. Stock will be transferred only when the stock transfer is closed.';
        }

        if ($stockTransferUpdateStatusData->status_id === StatusTypes::CANCELLED->value) {
            $storeTransferService->markAsCancelled(
                $stockTransferId,
                $companyId,
                $stockTransferUpdateStatusData->status_id,
                $user,
                $stockTransferUpdateStatusData->remarks,
            );
        }

        if ($stockTransferUpdateStatusData->status_id === StatusTypes::REJECTED->value) {
            $this->markAsRejected(
                $stockTransferId,
                $companyId,
                $stockTransferUpdateStatusData->status_id,
                $user,
                $stockTransferUpdateStatusData->remarks,
            );
        }

        return to_route('admin.stock_transfers.index')->with('success', $message);
    }

    public function updateReceivedDateAndStatus(Request $request, int $stockTransferId): void
    {
        $validatedData = $request->validate([
            'received_date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        /** @var Admin $user */
        $user = $request->user();

        $companyId = session('admin_company_id');

        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferService->markAsReceived($companyId, $stockTransferId, $validatedData['received_date'], $user);
    }

    public function deliveryNote(int $stockTransferId): Response
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $companyId = session('admin_company_id');

        $stockTransfer = $this->stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);

        if ($stockTransfer->status !== StatusTypes::RECEIVED->value) {
            throw new RedirectBackWithErrorException('Stock Transfer should be received to delivery notes.');
        }

        $stockTransferItems = $stockTransferItemQueries->getByStockTransferId($stockTransferId, $companyId);

        return Inertia::render('stock_transfers/DeliveryNote', [
            'stockTransferItems' => StockTransferItemDeliveryNoteResource::collection($stockTransferItems),
            'stockTransferId' => $stockTransferId,
            'stockTransferLocations' => $stockTransfer,
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
            'statuses' => [
                'discrepancy' => StatusTypes::DISCREPANCY->value,
            ],
            'discrepancyTypes' => [
                'positive' => StockTransferDiscrepancyTypes::POSITIVE->value,
                'negative' => StockTransferDiscrepancyTypes::NEGATIVE->value,
            ],
            'mimeTypes' => MimeTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function updateReceivedQuantities(Request $request, int $stockTransferId): void
    {
        $validatedData = $request->validate([
            'item_id' => ['required', 'integer'],
            'received_quantity' => ['required', 'numeric'],
            'status' => ['nullable', 'integer'],
        ]);

        DB::beginTransaction();

        try {
            $companyId = session('admin_company_id');

            $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
            $stockTransferItemQueries->updateReceivedQuantityAndDiscrepancyStatusByIdAndStockTransferId(
                $validatedData,
                $stockTransferId,
                $companyId
            );

            $stockTransfer = $this->stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);

            if ($stockTransfer->getStatus() === StatusTypes::RECEIVED->value) {
                $stockTransferItemQueries->removeDiscrepancyProof($validatedData['item_id']);
            }

            $this->stockTransferQueries->setUpdatedAt($stockTransfer);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Store-Manager-Update-Received-Quantities', [
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

    public function closeStockTransfer(Request $request, int $stockTransferId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $receivedStatus = StatusTypes::RECEIVED->value;

        $stockTransferService = resolve(StockTransferService::class);
        $stockTransfer = $this->stockTransferQueries->getByIdWithItemsAndUnits($stockTransferId, $companyId);

        if ($stockTransfer->getStatus() === StatusTypes::CLOSED->value) {
            throw new RedirectBackWithErrorException('The specified stock transfer has already been closed.');
        }

        if ($stockTransfer->getStatus() !== $receivedStatus) {
            throw new RedirectBackWithErrorException(
                'The Stock Transfer status should be received in order to change it to closed'
            );
        }

        $anyReceivedQuantityPending = $stockTransfer->getItems()->contains(
            fn ($item): bool => null === $item->received_quantity || (float) $item->received_quantity === 0.0
        );

        if ($anyReceivedQuantityPending) {
            throw new RedirectBackWithErrorException('one of the stock transfer item received quantity pending.');
        }

        /** @var User $user */
        $user = $request->user();

        DB::beginTransaction();

        try {
            $stockTransferService->closeTransfer($stockTransfer, $user, $companyId, $receivedStatus);

            DB::commit();

            return to_route('admin.stock_transfers.index')->with('success', 'Stock Transfer Closed Successfully.');
        } catch (Throwable $throwable) {
            Log::error('Admin-Close-Stock-Transfer', [
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

    public function editRequestOrder(int $stockTransferId): Response
    {
        $companyId = session('admin_company_id');

        $stockTransfer = $this->stockTransferQueries->getByIdWithItemsForEditRequestOrder($stockTransferId, $companyId);

        if (
            $stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value &&
            $stockTransfer->status !== StatusTypes::OPEN->value
        ) {
            throw new RedirectBackWithErrorException(
                'Stock Transfer should be open & request order type to edit the records.'
            );
        }

        $stockTransferService = resolve(StockTransferService::class);

        [$sourceInventories, $destinationInventories] = $stockTransferService->getStocks($stockTransfer);

        $stockTransfer['source_inventories'] = $sourceInventories;
        $stockTransfer['destination_inventories'] = $destinationInventories;

        return Inertia::render('stock_transfers/RequestOrderEditByDestination', [
            'stockTransfer' => new StockTransferRequestOrderEditResource($stockTransfer),
        ]);
    }

    public function discrepancy(int $stockTransferId): Response
    {
        $companyId = session('admin_company_id');
        $stockTransfer = $this->stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);

        if ($stockTransfer->status !== StatusTypes::DISCREPANCY->value) {
            throw new RedirectBackWithErrorException('Stock Transfer should be discrepancy to edit the records.');
        }

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItems = $stockTransferItemQueries->getByStockTransferIdWithProductAndBatches(
            $stockTransferId,
            $companyId
        );

        if ($stockTransferItems->isEmpty()) {
            throw new RedirectWithErrorException(
                'admin.stock_transfers.index',
                'This Stock Transfer does not have any discrepancies'
            );
        }

        return Inertia::render('stock_transfers/Discrepancy', [
            'stockTransferItems' => StockTransferItemDiscrepancyResource::collection($stockTransferItems),
            'stockTransferId' => $stockTransferId,
            'discrepancyTypes' => [
                'positive' => StockTransferDiscrepancyTypes::POSITIVE->value,
                'negative' => StockTransferDiscrepancyTypes::NEGATIVE->value,
            ],
            'mimeTypes' => MimeTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function discrepancyProof(Request $request, int $stockTransferId, int $stockTransferItemId): RedirectResponse
    {
        $validatedData = $request->validate([
            'discrepancy_proof' => [
                'required',
                'file',
                'mimetypes:' . implode(',', MimeTypes::getCasesValue()->toArray()),
                'max:' . config('services.max_upload_size'),
            ],
        ]);

        $stockTransfer = $this->stockTransferQueries->getLocationAndStatusById(
            $stockTransferId,
            session('admin_company_id')
        );

        if ($stockTransfer->getStatus() !== StatusTypes::RECEIVED->value) {
            throw new RedirectBackWithErrorException(
                'The status of the stock transfer should be received in order to upload the discrepancy proof'
            );
        }

        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        $stockTransferItemQueries->uploadDiscrepancyProof($validatedData, $stockTransferItemId);

        $this->stockTransferQueries->setUpdatedAtById($stockTransferId);

        return back()->with('success', 'The discrepancy proof has been uploaded successfully.');
    }

    public function removeDiscrepancyProof(int $stockTransferItemId): RedirectResponse
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);

        $stockTransferItemQueries->removeDiscrepancyProof($stockTransferItemId);

        return back()->with('success', 'Discrepancy proof removed successfully.');
    }

    public function closeDiscrepancy(Request $request, int $stockTransferId): RedirectResponse
    {
        $validatedData = $request->validate([
            'stock_transfer_items' => ['required', 'array'],
            'stock_transfer_items.*.id' => ['required', 'integer'],
            'stock_transfer_items.*.batch_details' => ['nullable', 'array'],
            'stock_transfer_items.*.batch_details.*.batch_number' => ['nullable', 'string'],
            'stock_transfer_items.*.batch_details.*.quantity' => ['nullable', 'numeric'],
        ]);

        $companyId = session('admin_company_id');

        $stockTransfer = $this->stockTransferQueries->getByIdWithItemsBatchesAndUnits($stockTransferId, $companyId);
        $discrepancyStatus = StatusTypes::DISCREPANCY->value;

        if ($stockTransfer->getStatus() !== $discrepancyStatus) {
            throw new RedirectBackWithErrorException(
                'The Stock Transfer status should indicate a discrepancy in order to close the Stock Transfer'
            );
        }

        $anyReceivedQuantityPending = $stockTransfer->getItems()->contains(
            fn ($item): bool => null === $item->received_quantity
        );

        if ($anyReceivedQuantityPending) {
            throw new RedirectBackWithErrorException('one of the stock transfer item received quantity pending.');
        }

        $stockTransferService = resolve(StockTransferService::class);
        [$products, $sourceInventories, $batches] = $stockTransferService->fetchProductsWithArchivedAndSourceInventories(
            $stockTransfer,
            $companyId
        );

        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);

        $stockTransferCheckRequestService->checkClosingDiscrepancyRequestBatchDetails(
            $stockTransfer,
            $validatedData,
            $products,
            $sourceInventories,
            $batches
        );

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $stockTransferService->closeDiscrepancy(
                $stockTransfer,
                $validatedData,
                $user,
                $products,
                $companyId,
                $discrepancyStatus,
                $batches
            );

            DB::commit();

            return to_route('admin.stock_transfers.index')->with('success', 'Stock Transfer closed Successfully.');
        } catch (Throwable $throwable) {
            Log::error('Admin-Stock-Transfer-Close-Discrepancy', [
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

    public function setReceivedQuantitySameAsQuantity(int $stockTransferId): RedirectResponse
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItemQueries->setReceivedQuantitySameAsQuantity($stockTransferId, session('admin_company_id'));

        return back()->with(
            'success',
            'The received quantity has been successfully set to match the specified quantity.'
        );
    }

    public function shippingDetails(int $stockTransferId): Response
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $companyId = session('admin_company_id');

        $stockTransferItems = $stockTransferItemQueries->getByStockTransferId($stockTransferId, $companyId);

        return Inertia::render('stock_transfers/ShippingDetails', [
            'stockTransferId' => $stockTransferId,
            'stockTransferItems' => StockTransferShipResource::collection($stockTransferItems),
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
        ]);
    }

    public function updateShippingDetailsAndMarkAsApproved(Request $request, int $stockTransferId): RedirectResponse
    {
        $validatedData = $request->validate([
            'stock_transfer_items' => ['required', 'array'],
            'stock_transfer_items.*.id' => ['required', 'integer'],
            'stock_transfer_items.*.package_type_id' => ['nullable', 'integer'],
            'stock_transfer_items.*.package_quantity' => ['nullable', 'integer'],
            'stock_transfer_items.*.package_total_quantity' => ['nullable', 'numeric'],
            'stock_transfer_items.*.batch_details' => ['nullable', 'array'],
            'stock_transfer_items.*.batch_details.*.batch_number' => ['nullable', 'string'],
            'stock_transfer_items.*.batch_details.*.quantity' => ['nullable', 'numeric'],
        ]);

        $companyId = session('admin_company_id');
        $openStatus = StatusTypes::OPEN->value;

        $stockTransfer = $this->stockTransferQueries->getByIdWithItemsAndBatches($stockTransferId, $companyId);

        if ($stockTransfer->transfer_type !== StockTransferTypes::REQUEST_ORDER->value) {
            throw new RedirectBackWithErrorException('The stock transfer request order cannot be approved.');
        }

        if ($stockTransfer->getStatus() !== $openStatus) {
            throw new RedirectBackWithErrorException(
                'The Stock Transfer status should be open in order to change it to approved'
            );
        }

        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);

        /** @var array $validatedData */
        $validatedData = $validatedData['stock_transfer_items'];
        $validatedData = collect($validatedData);

        $stockTransferService = resolve(StockTransferService::class);
        [$products, $batches, $derivatives] = $stockTransferService->fetchProductsBatchesAndDerivatives(
            $stockTransfer,
            $companyId
        );

        $stockTransferCheckRequestService->checkShippingDetails(
            $validatedData,
            $stockTransfer,
            $products,
            $batches,
            $derivatives
        );

        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $stockTransferService->updateShippingDetailsAndMarkAsApproved(
                $stockTransfer,
                $validatedData,
                $user,
                $companyId,
                $openStatus,
                $products,
                $batches
            );

            DB::commit();

            return to_route('admin.stock_transfers.index')->with(
                'success',
                'The specified stock transfer has been marked as approved successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error('Admin-Batch-details-Ship', [
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

    public function printStockTransfer(int $stockTransferId, string $transferType): string
    {
        $stockTransferPrintService = resolve(StockTransferPrintService::class);

        return $stockTransferPrintService->printStockTransfer(
            $stockTransferId,
            $transferType,
            session('admin_company_id'),
            null,
            null
        );
    }

    public function updateRequestOrder(
        Request $request,
        StockTransferRequestOrderData $stockTransferRequestOrderData,
        int $stockTransferId
    ): RedirectResponse {
        $companyId = session('admin_company_id');

        $stockTransfer = $this->stockTransferQueries->getByIdForRequestOrder($stockTransferId, $companyId);

        if (
            $stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value &&
            $stockTransfer->status !== StatusTypes::OPEN->value
        ) {
            throw new RedirectBackWithErrorException(
                'Stock Transfer should be open & request order type to updates the records.'
            );
        }

        $stockTransferService = resolve(StockTransferService::class);

        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
        $stockTransferCheckRequestService->locationChanged($stockTransfer, $stockTransferRequestOrderData);

        $productIds = collect($stockTransferRequestOrderData->transfer_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        [$products, $batches, $inventories, $derivatives] = $stockTransferService->prepareActiveBatchesProductsAndInventories(
            $productIds,
            $companyId,
            $stockTransferRequestOrderData->source_location_id,
        );

        $stockTransferCheckRequestService->checkRequestDetails(
            $stockTransferRequestOrderData,
            $products,
            $inventories,
            $batches,
            $derivatives
        );

        /** @var Admin $admin */
        $admin = $request->user();

        DB::beginTransaction();

        try {
            $stockTransferService->updateRequestOrder(
                $stockTransferRequestOrderData,
                $stockTransfer,
                $companyId,
                $admin,
                StatusTypes::OPEN->value,
                $products,
                $inventories,
                $derivatives
            );

            DB::commit();

            return to_route('admin.stock_transfers.index')
                ->with('success', 'The stock transfer has been updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update: Admin Stock Transfer Request Order', [
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

    public function exportStockTransfers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'stock_transfer_date' => $request->get('stock_transfer_date'),
            'location_id' => $request->get('location_id'),
            'select_status' => $request->get('select_status'),
            'transfer_type' => $request->get('transfer_type'),
            'stock_transfer_id' => null,
            'dashboard_transfer_type' => $request->get('dashboard_transfer_type'),
        ];

        if (null !== $filterData['transfer_type'] && null !== $filterData['dashboard_transfer_type']) {
            $filterData['dashboard_transfer_type'] = null;
        }

        if (null !== $request->get('stock_transfer_number')) {
            $filterData['search_text'] = $request->get('stock_transfer_number');
        }

        if (null !== $request->get('stock_transfer_id')) {
            $filterData['stock_transfer_id'] = $request->get('stock_transfer_id');
        }

        $stockTransfers = $this->stockTransferQueries->getStockTransfersExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new StockTransferExport($stockTransfers, $filterData), $filename);
    }

    public function updateAdditionalItems(Request $request, int $stockTransferId): void
    {
        $validatedData = $request->validate([
            'additional_items' => ['required', 'array'],
            'additional_items.*.stock_transfer_id' => ['required', 'integer'],
            'additional_items.*.product_id' => ['required', 'integer'],
            'additional_items.*.has_batch' => ['required', 'boolean'],
            'additional_items.*.package_type_id' => ['nullable', 'integer'],
            'additional_items.*.unit_of_measure_derivative_id' => ['nullable', 'integer'],
            'additional_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'additional_items.*.received_quantity' => ['required', 'numeric', 'min:0.01'],
            'additional_items.*.package_quantity' => ['nullable', 'numeric', 'min:0'],
            'additional_items.*.package_total_quantity' => ['nullable', 'numeric', 'min:0'],
            'additional_items.*.remarks' => ['nullable', 'string'],
        ]);

        $companyId = session('admin_company_id');
        $requestData = $request->all();
        $stockTransferService = resolve(StockTransferService::class);

        $stockTransfer = $this->stockTransferQueries->getLocationAndStatusById($stockTransferId, $companyId);

        if ($stockTransfer->status !== StatusTypes::RECEIVED->value) {
            throw new RedirectBackWithErrorException('Stock Transfer should be received to updates the records.');
        }

        /** @var array $additionalItems */
        $additionalItems = $validatedData['additional_items'];
        $productIds = collect($additionalItems)->pluck('product_id')->unique()->filter()->toArray();

        $products = $stockTransferService->fetchProducts($productIds, $companyId);

        if (config('app.product_variant')) {
            $derivatives = $stockTransferService->fetchDerivatives(
                $products->pluck('masterProduct.unit_of_measure_id')->unique()->filter()->toArray()
            );
        } else {
            $derivatives = $stockTransferService->fetchDerivatives(
                $products->pluck('unit_of_measure_id')->unique()->filter()->toArray()
            );
        }

        $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
        $stockTransferCheckRequestService->checkAdditionalItemsRequest(
            $requestData,
            $products,
            $stockTransferId,
            $derivatives
        );

        DB::beginTransaction();

        try {
            /** @var User $admin */
            $admin = $request->user();

            $stockTransferService->updateAdditionalItems($additionalItems, $admin, $derivatives);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Admin Additional item received', [
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

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function fetchStockTransferItemByStockTransferId(int $transferId): array
    {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $stockTransferItems = $stockTransferItemQueries->getByStockTransferId($transferId, session('admin_company_id'));

        return [
            'stock_transfer_items' => StockTransferItemsListResource::collection($stockTransferItems),
        ];
    }

    public function removeAdditionalItem(int $stockTransferItemId): void
    {
        DB::beginTransaction();

        try {
            $stockTransferService = resolve(StockTransferService::class);
            $stockTransferService->removeAdditionalItem($stockTransferItemId);

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

    public function deliveryNoteItemRemarks(Request $request, int $stockTransferItemId): void
    {
        $validatedData = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        /** @var User $admin */
        $admin = $request->user();

        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferService->deliveryNoteItemRemarks($admin, $validatedData['remarks'], $stockTransferItemId);
    }

    public function markAsShippedOrTransit(
        Request $request,
        StockTransferShippedData $stockTransferShippedData,
        int $stockTransferId
    ): void {
        $companyId = session('admin_company_id');
        /** @var Admin $user */
        $user = $request->user();

        if ($stockTransferShippedData->shipped_type === ShippedTypes::TRANSIT->value) {
            $stockTransferCheckRequestService = resolve(StockTransferCheckRequestService::class);
            $stockTransferCheckRequestService->validateTransitLocation(
                $stockTransferShippedData,
                $stockTransferId,
                $companyId
            );
        }

        $stockTransferService = resolve(StockTransferService::class);
        $stockTransferService->markAsShippedOrTransit($stockTransferShippedData, $stockTransferId, $companyId, $user);
    }

    public function fetchAggregateAverageDays(Request $request): array
    {
        $validatedData = $request->validate([
            'source_location_id' => ['required', 'integer'],
            'destination_location_id' => ['required', 'integer'],
        ]);

        $stockTransferService = resolve(StockTransferService::class);

        return $stockTransferService->getAverageAggregateDays($validatedData);
    }

    private function markAsRejected(
        int $stockTransferId,
        int $companyId,
        int $statusId,
        User $admin,
        ?string $remarks = null
    ): void {
        $stockTransfer = $this->stockTransferQueries->getByIdWithItemsBatchesAndUnits($stockTransferId, $companyId);
        $openStatus = StatusTypes::OPEN->value;

        if ($stockTransfer->getStatus() !== $openStatus) {
            throw new RedirectBackWithErrorException(
                'The Stock Transfer status must be open in order to mark it as rejected'
            );
        }

        $stockTransferService = resolve(StockTransferService::class);

        if ($stockTransfer->transfer_type === StockTransferTypes::REQUEST_ORDER->value) {
            $stockTransferService->requestOrderMarkAsRejected(
                $stockTransfer,
                $statusId,
                $openStatus,
                $companyId,
                $admin,
                $remarks
            );

            return;
        }

        if ($stockTransfer->transfer_type === StockTransferTypes::TRANSFER_ORDER->value) {
            $stockTransferService->revertBackInventory(
                $stockTransfer,
                $admin,
                $companyId,
                $openStatus,
                $statusId,
                'rejected',
                'source',
                $remarks
            );
        }
    }
}
