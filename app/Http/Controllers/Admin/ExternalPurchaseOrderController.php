<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\ExternalPurchaseOrder\DataObjects\ExternalPurchaseOrderData;
use App\Domains\ExternalPurchaseOrder\Enums\Statuses;
use App\Domains\ExternalPurchaseOrder\ExternalPurchaseOrderQueries;
use App\Domains\ExternalPurchaseOrder\Services\ExternalPurchaseOrderPrintService;
use App\Domains\ExternalPurchaseOrder\Services\ExternalPurchaseOrderService;
use App\Domains\ExternalPurchaseOrderItem\Export\ExternalPurchaseOrderItemExport;
use App\Domains\ExternalPurchaseOrderItem\ExternalPurchaseOrderItemQueries;
use App\Domains\ExternalPurchaseOrderItem\Resource\ExternalPurchaseOrderItemResource;
use App\Domains\ExternalPurchaseOrderItem\Services\ExternalPurchaseOrderItemCheckRequestService;
use App\Domains\ExternalPurchaseOrderItem\Services\ExternalPurchaseOrderItemService;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\PurchasePlanItem\Resource\PurchasePlanShippingEditItemsResource;
use App\Domains\PurchasePlanItem\Resource\PurchasePlanShippingItemsResource;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExternalPurchaseOrderController extends Controller
{
    public function __construct(
        protected ExternalPurchaseOrderQueries $externalPurchaseOrderQueries
    ) {
    }

    public function index(int $purchasePlanId): Response
    {
        $companyId = session('admin_company_id');
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlan = $purchasePlanQueries->getById($purchasePlanId, $companyId);
        $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);

        return Inertia::render('external_purchase_orders/Index', [
            'purchasePlan' => $purchasePlan,
            'statuses' => Statuses::getStatuses(),
            'externalPurchaseOrderStatuses' => Statuses::getList(),
            'hasPurchaseOrderItems' => $externalPurchaseOrderService->hasPurchasePlanItems($purchasePlan),
        ]);
    }

    public function fetchExternalPurchaseOrders(Request $request): array
    {
        $purchasePlaneId = (int) $request->get('purchase_plan_id');
        $companyId = session('admin_company_id');
        $filterData = $this->getPreparedFilters($request);
        $filterData['purchase_plane_id'] = $purchasePlaneId;
        $filterData['company_id'] = $companyId;
        $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);

        return $externalPurchaseOrderService->fetchExternalPurchaseOrders($filterData);
    }

    public function fetchExternalPurchaseOrderItemById(int $externalPurchaseOrderId): array
    {
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $externalPurchaseOrderItems = $externalPurchaseOrderItemQueries->getByExternalPurchaseOrderId(
            $externalPurchaseOrderId,
        );

        return [
            'external_purchase_order_items' => ExternalPurchaseOrderItemResource::collection(
                $externalPurchaseOrderItems
            ),
            'totals' => [
                'quantity' => $externalPurchaseOrderItems->sum('quantity'),
                'received' => $externalPurchaseOrderItems->sum('received_quantity'),
            ],
        ];
    }

    public function print(int $externalPurchaseOrderId): string
    {
        $externalPurchaseOrderPrintService = resolve(ExternalPurchaseOrderPrintService::class);

        return $externalPurchaseOrderPrintService->print($externalPurchaseOrderId);
    }

    public function exportExternalPurchaseOrderItems(
        int $externalPurchaseOrderId,
        string $fileName
    ): BinaryFileResponse {
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $externalPurchaseOrderItems = $externalPurchaseOrderItemQueries->getByExternalPurchaseOrderId(
            $externalPurchaseOrderId,
        );

        return Excel::download(new ExternalPurchaseOrderItemExport($externalPurchaseOrderItems), $fileName);
    }

    public function create(int $purchasePlanId): Response
    {
        $companyId = session('admin_company_id');

        $purchasePlanQueries = resolve(PurchasePlanQueries::class);

        $purchasePlan = $purchasePlanQueries->getByPurchasePlanIdForCreateEpo($purchasePlanId, $companyId);

        return Inertia::render('external_purchase_orders/Manage', [
            'purchasePlan' => $purchasePlan,
            'transferItems' => PurchasePlanShippingItemsResource::collection($purchasePlan->items),
        ]);
    }

    public function store(ExternalPurchaseOrderData $externalPurchaseOrderData, int $purchasePlanId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $productIds = collect($externalPurchaseOrderData->transfer_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderItemService::class);

        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlan = $purchasePlanQueries->getByIdAndCompanyIdWithItems($purchasePlanId, $companyId);

        $routeUrl = 'admin.purchase_plans.index';
        $externalPurchaseOrderItemService->checkAllItemsReceived($purchasePlan->items, $routeUrl);

        [$products] = $externalPurchaseOrderItemService->prepareActiveBatchesProducts($productIds, $companyId);

        $externalPurchaseOrderItemCheckRequestService = resolve(ExternalPurchaseOrderItemCheckRequestService::class);
        $externalPurchaseOrderItemCheckRequestService->checkRequestDetails($externalPurchaseOrderData, $products);

        DB::beginTransaction();

        try {
            $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);
            $externalPurchaseOrder = $externalPurchaseOrderService->saveExternalPurchaseOrder(
                $externalPurchaseOrderData,
                $purchasePlan
            );

            $externalPurchaseOrderItemService->addShippingDetails(
                $externalPurchaseOrderData,
                $externalPurchaseOrder,
            );

            DB::commit();

            return to_route('admin.external_purchase_orders.index', $purchasePlanId)
                ->with('success', 'External Purchase Order is created successfully.');
        } catch (Throwable $throwable) {
            Log::error([
                'external purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $externalPurchaseOrderId): Response
    {
        $companyId = session('admin_company_id');

        $externalPurchaseOrder = $this->externalPurchaseOrderQueries->getByIdWithRelationForEdit(
            $externalPurchaseOrderId
        );

        $purchasePlanQueries = resolve(PurchasePlanQueries::class);
        $purchasePlan = $purchasePlanQueries->getByPurchasePlanIdForCreateEpo(
            $externalPurchaseOrder->purchase_plan_id,
            $companyId
        );

        return Inertia::render('external_purchase_orders/Manage', [
            'externalPurchaseOrder' => new PurchasePlanShippingEditItemsResource($externalPurchaseOrder),
            'purchasePlan' => $purchasePlan,
        ]);
    }

    public function update(
        ExternalPurchaseOrderData $externalPurchaseOrderData,
        int $externalPurchaseOrderId
    ): RedirectResponse {
        $externalPurchaseOrder = $this->externalPurchaseOrderQueries->getById($externalPurchaseOrderId);
        $companyId = session('admin_company_id');

        $productIds = collect($externalPurchaseOrderData->transfer_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderItemService::class);

        [$products] = $externalPurchaseOrderItemService->prepareActiveBatchesProducts($productIds, $companyId);

        $externalPurchaseOrderItemCheckRequestService = resolve(ExternalPurchaseOrderItemCheckRequestService::class);
        $externalPurchaseOrderItemCheckRequestService->checkRequestDetails($externalPurchaseOrderData, $products);

        DB::beginTransaction();

        try {
            $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);
            $externalPurchaseOrderService->updateExternalPurchaseOrder(
                $externalPurchaseOrderData,
                $externalPurchaseOrder->id
            );

            $externalPurchaseOrderItemService->updateShippingDetails(
                $externalPurchaseOrderData,
                $externalPurchaseOrder,
            );

            DB::commit();

            return to_route('admin.external_purchase_orders.index', $externalPurchaseOrder->purchase_plan_id)
                ->with('success', 'External Purchase Order is updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update External Purchase Order', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function getPreparedFilters(Request $request): array
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

    public function markAsCancel(Request $request, int $externalPurchaseOrderId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $externalPurchaseOrder = $this->externalPurchaseOrderQueries->getByIdWithForCancel(
            $externalPurchaseOrderId,
            $companyId
        );

        $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        DB::beginTransaction();

        try {
            $externalPurchaseOrderService->markAsCancel($externalPurchaseOrder, $admin);

            DB::commit();

            return to_route(
                'admin.external_purchase_orders.index',
                $externalPurchaseOrder->purchasePlan?->id
            )->with('success', 'External Purchase Order canceled successfully');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsApprove(Request $request, int $externalPurchaseOrderId): RedirectResponse
    {
        $companyId = session('admin_company_id');
        $externalPurchaseOrder = $this->externalPurchaseOrderQueries->getByIdWith($externalPurchaseOrderId, $companyId);

        $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        DB::beginTransaction();

        try {
            $externalPurchaseOrderService->markAsApprove($externalPurchaseOrder, $admin);

            DB::commit();

            return to_route(
                'admin.external_purchase_orders.index',
                $externalPurchaseOrder->purchasePlan?->id
            )->with('success', 'External Purchase Order approve successfully');
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }
}
