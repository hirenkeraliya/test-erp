<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\PurchaseOrder\DataObjects\PurchaseOrderData;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Resource\PurchaseOrderEditResource;
use App\Domains\PurchaseOrder\Services\PurchaseOrderCheckRequestService;
use App\Domains\PurchaseOrder\Services\PurchaseOrderPrintService;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderQueries $purchaseOrderQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        $orderNumber = $request->get('order_number');

        return Inertia::render('purchase_orders/Index', [
            'statuses' => Statuses::getStatuses(),
            'status' => Statuses::getList(),
            'orderType' => OrderTypes::getList(),
            'orderTypes' => [
                'purchase_request' => OrderTypes::PURCHASE_REQUEST->value,
                'transfer_request' => OrderTypes::TRANSFER_REQUEST->value,
                'purchase_order' => OrderTypes::PURCHASE_ORDER->value,
                'sales_order' => OrderTypes::SALES_ORDER->value,
            ],
            'fulFillmentStatuses' => FulfillmentStatuses::getStatuses(),
            'stores' => $stores,
            'warehouses' => $warehouses,
            'externalCompanies' => $externalCompanies,
            'orderNumber' => $orderNumber > 0 ? $orderNumber : null,
            'exportPermission' => PermissionList::getExportPermissionName('purchase_order'),
            'dashboardFilterData' => [
                'order_type' => (int) $request->get('order_type') > 0 ? (int) $request->get('order_type') : null,
                'select_status' => (int) $request->get('select_status') > 0 ? (int) $request->get(
                    'select_status'
                ) : null,
                'location_id' => (int) $request->get('location_id') > 0 ? (int) $request->get('location_id') : null,
            ],
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    public function fetchPurchaseOrders(Request $request): array
    {
        $filterData = $this->getPreparedFilters($request);

        $companyId = session('admin_company_id');

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        return $purchaseOrderService->fetchPurchaseOrders($filterData, $companyId);
    }

    public function create(int $orderType): Response
    {
        $companyId = session('admin_company_id');

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        return Inertia::render('purchase_orders/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'externalCompanies' => $externalCompanies,
            'orderTypes' => OrderTypes::getList(),
            'staticDetails' => [
                'transfer_request' => OrderTypes::TRANSFER_REQUEST->value,
                'purchase_request' => OrderTypes::PURCHASE_REQUEST->value,
                'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            ],
            'locationTypes' => LocationTypes::getList(),
            'defaultOrderType' => $orderType,
            'createdByCompanyId' => $companyId,
        ]);
    }

    public function store(PurchaseOrderData $purchaseOrderData): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $purchaseOrderCheckRequestService = resolve(PurchaseOrderCheckRequestService::class);
        $products = $purchaseOrderCheckRequestService->getProducts($companyId, $purchaseOrderData);
        $purchaseOrderCheckRequestService->checkRequestDetails($products, $purchaseOrderData);
        DB::beginTransaction();

        try {
            $purchaseOrderService = resolve(PurchaseOrderService::class);
            $purchaseOrderService->savePurchaseOrder($purchaseOrderData->all(), $companyId, $products);

            DB::commit();

            if ($purchaseOrderData->order_type === OrderTypes::TRANSFER_REQUEST->value) {
                return to_route('admin.purchase_orders.index')
                    ->with('success', 'Transfer Request is created successfully.');
            }

            return to_route('admin.purchase_orders.index')
                ->with('success', 'Purchase Request is created successfully.');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function cancel(Request $request, int $purchaseOrderId): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        $purchaseOrder = $this->purchaseOrderQueries->getByIdAndCompanyIdWithItems(
            $purchaseOrderId,
            session('admin_company_id')
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->checkMarkAsCanceled($purchaseOrder);

        DB::beginTransaction();

        try {
            $purchaseOrderService->purchaseOrderMarkAsCanceled($purchaseOrder, $admin);

            DB::commit();

            return to_route('admin.purchase_orders.index')->with(
                'success',
                'The specified purchase order has been marked as canceled successfully'
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

    public function approve(Request $request, int $purchaseOrderId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation($purchaseOrderId, $companyId);

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->checkPurchaseOrderApprove($purchaseOrder);

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        DB::beginTransaction();

        try {
            $purchaseOrderService->purchaseOrderApprove($purchaseOrder, $admin);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }

        return to_route('admin.purchase_orders.index')->with(
            'success',
            'The specified purchase order has been marked as approved successfully'
        );
    }

    public function reject(Request $request, int $purchaseOrderId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation($purchaseOrderId, $companyId);

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderService->checkMarkAsRejected($purchaseOrder);

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        DB::beginTransaction();

        try {
            $purchaseOrderService->purchaseOrderMarkAsRejected($purchaseOrder, $admin);

            DB::commit();

            return to_route('admin.purchase_orders.index')->with(
                'success',
                'The specified purchase order has been marked as rejected successfully'
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

    public function open(Request $request, int $purchaseOrderId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation($purchaseOrderId, $companyId);

        if ($purchaseOrder->status !== Statuses::DRAFT->value) {
            throw new RedirectWithErrorException(
                'admin.purchase_orders.index',
                'At this moment, opening the purchase order is not possible as it currently does not have a draft status.'
            );
        }

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $purchaseOrderCheckRequestService = resolve(PurchaseOrderCheckRequestService::class);

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value) {
            $purchaseOrderCheckRequestService->checkExternalStockBeforeProceeding($purchaseOrder);
        }

        DB::beginTransaction();

        try {
            $dataPurchaseOrderId = $purchaseOrderService->openPurchaseOrderAndSyncExternalData(
                $admin,
                $purchaseOrder,
                $companyId
            );

            DB::commit();

            $purchaseOrderService->postAutoApproveExternalSalesOrder($dataPurchaseOrderId, $companyId);

            return to_route('admin.purchase_orders.index')->with(
                'success',
                'The specified purchase order has been marked as open successfully'
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

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function fetchPurchaseOrderItemByPurchaseOrderId(int $purchaseOrderId): array
    {
        $companyId = session('admin_company_id');

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        return $purchaseOrderService->fetchPurchaseOrderItemByPurchaseOrderId($purchaseOrderId, $companyId);
    }

    public function exportPurchaseOrderItems(int $purchaseOrderId, string $fileName): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        return $purchaseOrderService->exportPurchaseOrderItems($purchaseOrderId, $companyId, $fileName);
    }

    public function edit(int $purchaseOrderId): Response
    {
        $companyId = session('admin_company_id');
        $purchaseOrder = $this->purchaseOrderQueries->getByIdWithItems($purchaseOrderId, $companyId);

        $purchaseOrderCheckRequestService = resolve(PurchaseOrderCheckRequestService::class);
        if (! $purchaseOrderCheckRequestService->isPurchaseOrderEdit($purchaseOrder)) {
            throw new RedirectWithErrorException(
                'admin.purchase_orders.index',
                'The purchase order is locked for editing as it is currently not in draft or open status.'
            );
        }

        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompanies = $externalCompanyQueries->getAll();

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalLocations = $externalLocationQueries->getAll($purchaseOrder->external_company_id);

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        $purchaseOrder['source_inventories'] = $purchaseOrderService->getStocks($purchaseOrder);
        $purchaseOrder['external_inventories'] = $purchaseOrderService->getExternalStocks($purchaseOrder);

        return Inertia::render('purchase_orders/Manage', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'externalStores' => $externalLocations->where('type_id', LocationTypes::STORE->value)->values(),
            'externalWarehouses' => $externalLocations->where('type_id', LocationTypes::WAREHOUSE->value)->values(),
            'externalCompanies' => $externalCompanies,
            'orderTypes' => OrderTypes::getList(),
            'staticDetails' => [
                'transfer_request' => OrderTypes::TRANSFER_REQUEST->value,
                'purchase_request' => OrderTypes::PURCHASE_REQUEST->value,
                'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            ],
            'locationTypes' => LocationTypes::getList(),
            'defaultOrderType' => $purchaseOrder->order_type,
            'purchaseOrder' => new PurchaseOrderEditResource($purchaseOrder),
        ]);
    }

    public function update(PurchaseOrderData $purchaseOrderData, int $purchaseOrderId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $purchaseOrderCheckRequestService = resolve(PurchaseOrderCheckRequestService::class);
        $products = $purchaseOrderCheckRequestService->getProducts($companyId, $purchaseOrderData);
        $purchaseOrderCheckRequestService->checkRequestDetails($products, $purchaseOrderData);

        DB::beginTransaction();

        try {
            $purchaseOrderService = resolve(PurchaseOrderService::class);
            $purchaseOrderService->update($products, $purchaseOrderData->all(), $companyId, $purchaseOrderId);

            DB::commit();

            return to_route('admin.purchase_orders.index')
                ->with('success', 'Purchase Order Request Update successfully.');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function print(int $purchaseOrderId): string
    {
        $purchaseOrderPrintService = resolve(PurchaseOrderPrintService::class);

        return $purchaseOrderPrintService->print($purchaseOrderId, session('admin_company_id'));
    }

    public function exportPurchaseOrders(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getPreparedFilters($request);

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        return $purchaseOrderService->exportPurchaseOrders($filterData, session('admin_company_id'), $filename);
    }

    public function getPreparedFilters(Request $request): array
    {
        return $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'order_type' => $request->get('order_type'),
            'select_status' => $request->get('select_status'),
            'date_range' => $request->get('date_range'),
            'location_id' => $request->get('location_id'),
            'external_location_id' => $request->get('external_location_id'),
            'order_number' => $request->get('order_number'),
        ];
    }
}
