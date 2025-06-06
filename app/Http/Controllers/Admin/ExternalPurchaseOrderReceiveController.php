<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Admin\AdminQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalPurchaseOrder\ExternalPurchaseOrderQueries;
use App\Domains\ExternalPurchaseOrder\Services\ExternalPurchaseOrderService;
use App\Domains\ExternalPurchaseOrderItem\Resource\ExternalPurchaseOrderEditItemReceiveResource;
use App\Domains\ExternalPurchaseOrderItem\Resource\ExternalPurchaseOrderItemReceiveResource;
use App\Domains\ExternalPurchaseOrderItem\Services\ExternalPurchaseOrderItemService;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\Export\ExternalPurchaseOrderPartialReceiveItemExport;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\ExternalPurchaseOrderPartialReceiveItemQueries;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\Resource\ExternalPurchaseOrderPartialReceiveItemResource;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\Services\ExternalPurchaseOrderPartialReceiveItemCheckRequestService;
use App\Domains\ExternalPurchaseOrderPartialReceiveItem\Services\ExternalPurchaseOrderPartialReceiveItemService;
use App\Domains\ExternalPurchaseOrderReceive\DataObjects\ExternalPurchaseOrderReceiveData;
use App\Domains\ExternalPurchaseOrderReceive\Enums\Statuses;
use App\Domains\ExternalPurchaseOrderReceive\ExternalPurchaseOrderReceiveQueries;
use App\Domains\ExternalPurchaseOrderReceive\Resource\ExternalPurchaseOrderReceiveListResource;
use App\Domains\ExternalPurchaseOrderReceive\Services\ExternalPurchaseOrderPartialReceivePrintService;
use App\Domains\ExternalPurchaseOrderReceive\Services\ExternalPurchaseOrderReceiveService;
use App\Domains\GoodsReceivedNote\Jobs\externalPurchaseOrderPartialReceiveJob;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ExternalPurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExternalPurchaseOrderReceiveController extends Controller
{
    public function __construct(
        protected ExternalPurchaseOrderReceiveQueries $externalPurchaseOrderReceiveQueries
    ) {
    }

    public function index(int $externalPurchaseOrderId): Response
    {
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrder = $externalPurchaseOrderQueries->getById($externalPurchaseOrderId);

        $externalPurchaseOrderReceiveService = resolve(ExternalPurchaseOrderReceiveService::class);

        return Inertia::render('external_purchase_order_partial_receives/Index', [
            'externalPurchaseOrder' => $externalPurchaseOrder,
            'statuses' => Statuses::getStatuses(),
            'hasPartialReceiveItems' => $externalPurchaseOrderReceiveService->hasPartialReceiveItems(
                $externalPurchaseOrder
            ),
        ]);
    }

    public function fetchExternalPurchaseOrderReceives(Request $request): array
    {
        $externalPurchaseOrderId = (int) $request->get('external_purchase_order_id');

        $filterData = $this->getPreparedFilters($request);
        $filterData['external_purchase_order_id'] = $externalPurchaseOrderId;

        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $lengthAwarePaginator = $externalPurchaseOrderReceiveQueries->listQuery($filterData, $externalPurchaseOrderId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ExternalPurchaseOrderReceiveListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(int $externalPurchaseOrderId): Response
    {
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);

        $externalPurchaseOrder = $externalPurchaseOrderQueries->getByIdForPartialReceive($externalPurchaseOrderId);

        return Inertia::render('external_purchase_order_partial_receives/Manage', [
            'externalPurchaseOrder' => $externalPurchaseOrder,
            'transferItems' => ExternalPurchaseOrderItemReceiveResource::collection($externalPurchaseOrder->items),
        ]);
    }

    public function store(
        Request $request,
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        int $externalPurchaseOrderId
    ): RedirectResponse {
        $companyId = session('admin_company_id');
        $productIds = collect($externalPurchaseOrderReceiveData->receive_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderPartialReceiveItemService = resolve(
            ExternalPurchaseOrderPartialReceiveItemService::class
        );
        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderItemService::class);

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrderService = resolve(ExternalPurchaseOrderService::class);

        $externalPurchaseOrder = $externalPurchaseOrderQueries->getByIdForPartialReceive($externalPurchaseOrderId);

        $routeUrl = 'admin.purchase_plans.index';

        $externalPurchaseOrderPartialReceiveItemService->checkAllItemsReceived(
            $externalPurchaseOrder->items,
            $routeUrl
        );

        [$products, $batches] = $externalPurchaseOrderItemService->prepareActiveBatchesProducts(
            $productIds,
            $companyId
        );

        $externalPurchaseOrderPartialReceiveItemCheckRequestService = resolve(
            ExternalPurchaseOrderPartialReceiveItemCheckRequestService::class
        );
        $externalPurchaseOrderPartialReceiveItemCheckRequestService->checkRequestDetails(
            $externalPurchaseOrderReceiveData,
            $products,
            $batches
        );

        DB::beginTransaction();

        try {
            $externalPurchaseOrderReceiveService = resolve(ExternalPurchaseOrderReceiveService::class);
            $externalPurchaseOrderReceive = $externalPurchaseOrderReceiveService->saveExternalPurchaseOrderReceive(
                $externalPurchaseOrderReceiveData,
                $externalPurchaseOrder
            );

            $externalPurchaseOrderPartialReceiveItemService->addReceiveDetails(
                $externalPurchaseOrderReceiveData,
                $externalPurchaseOrderReceive,
                $batches
            );

            /** @var Admin $admin */
            $admin = $request->user();

            $adminQueries = resolve(AdminQueries::class);
            $admin = $adminQueries->loadEmployee($admin);

            $externalPurchaseOrderService->markAsPartial($externalPurchaseOrder, $admin);

            DB::commit();

            return to_route('admin.external_purchase_order_receives.index', $externalPurchaseOrderId)
                ->with('success', 'External Purchase Order Partial Receive is created successfully.');
        } catch (Throwable $throwable) {
            Log::error([
                'external purchase order partial receive' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $externalPurchaseOrderPartialReceiveId): Response
    {
        $externalPurchaseOrderPartialReceive = $this->externalPurchaseOrderReceiveQueries->getByIdWithRelationForEdit(
            $externalPurchaseOrderPartialReceiveId
        );

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrder = $externalPurchaseOrderQueries->getByPartialReceiveIdForCreateEpopr(
            $externalPurchaseOrderPartialReceive->external_purchase_order_id,
        );

        return Inertia::render('external_purchase_order_partial_receives/Manage', [
            'externalPurchaseOrderPartialReceive' => new ExternalPurchaseOrderEditItemReceiveResource(
                $externalPurchaseOrderPartialReceive
            ),
            'externalPurchaseOrder' => $externalPurchaseOrder,
        ]);
    }

    public function update(
        ExternalPurchaseOrderReceiveData $externalPurchaseOrderReceiveData,
        int $externalPurchaseOrderReceiveId
    ): RedirectResponse {
        $companyId = session('admin_company_id');

        $externalPurchaseOrderReceive = $this->externalPurchaseOrderReceiveQueries->getById(
            $externalPurchaseOrderReceiveId
        );

        $productIds = collect($externalPurchaseOrderReceiveData->receive_items)->pluck(
            'product_id'
        )->unique()->filter()->toArray();

        $externalPurchaseOrderPartialReceiveItemService = resolve(
            ExternalPurchaseOrderPartialReceiveItemService::class
        );

        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);
        $externalPurchaseOrder = $externalPurchaseOrderQueries->getById(
            $externalPurchaseOrderReceive->external_purchase_order_id,
        );

        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderItemService::class);

        [$products, $batches] = $externalPurchaseOrderItemService->prepareActiveBatchesProducts(
            $productIds,
            $companyId
        );

        $externalPurchaseOrderPartialReceiveItemCheckRequestService = resolve(
            ExternalPurchaseOrderPartialReceiveItemCheckRequestService::class
        );
        $externalPurchaseOrderPartialReceiveItemCheckRequestService->checkRequestDetails(
            $externalPurchaseOrderReceiveData,
            $products,
            $batches
        );

        DB::beginTransaction();

        try {
            $externalPurchaseOrderReceiveService = resolve(ExternalPurchaseOrderReceiveService::class);
            $externalPurchaseOrderReceiveService->updateExternalPurchaseOrderReceive(
                $externalPurchaseOrderReceiveData,
                $externalPurchaseOrderReceive->id
            );

            $externalPurchaseOrderPartialReceiveItemService->updateReceiveDetails(
                $externalPurchaseOrderReceiveData,
                $externalPurchaseOrderReceive,
                $batches
            );

            DB::commit();

            return to_route('admin.external_purchase_order_receives.index', $externalPurchaseOrder->id)
                ->with('success', 'External Purchase Order Partial Receive is updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update External Purchase Order Partial Receive', [
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

    public function fetchExternalPurchaseOrderReceiveItemById(int $externalPurchaseOrderPartialReceiveId): array
    {
        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );
        $externalPurchaseOrderPartialReceiveItems = $externalPurchaseOrderPartialReceiveItemQueries->getByExternalPurchaseOrderPartialReceiveId(
            $externalPurchaseOrderPartialReceiveId,
        );
        $externalPurchaseOrderItem = $externalPurchaseOrderPartialReceiveItems->pluck('externalPurchaseOrderItem');

        return [
            'external_purchase_order_receive_items' => ExternalPurchaseOrderPartialReceiveItemResource::collection(
                $externalPurchaseOrderPartialReceiveItems
            ),
            'totals' => [
                'quantity' => $externalPurchaseOrderItem->sum('quantity'),
                'received' => $externalPurchaseOrderPartialReceiveItems->sum('quantity_received'),
            ],
        ];
    }

    public function getPreparedFilters(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];
    }

    public function completed(Request $request, int $externalPurchaseOrderReceiveId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        $externalPurchaseOrderReceive = $this->externalPurchaseOrderReceiveQueries->getById(
            $externalPurchaseOrderReceiveId
        );

        /** @var ExternalPurchaseOrder $externalPurchaseOrder */
        $externalPurchaseOrder = $externalPurchaseOrderReceive->externalPurchaseOrder;

        $externalPurchaseOrderReceiveService = resolve(ExternalPurchaseOrderReceiveService::class);
        $externalPurchaseOrderItemService = resolve(ExternalPurchaseOrderService::class);

        DB::beginTransaction();

        try {
            $externalPurchaseOrderReceiveService->purchasePlanMarkAsCompleted($externalPurchaseOrderReceive);

            $externalPurchaseOrderItemService->markAsCompletedExternalPurchaseOrder(
                $externalPurchaseOrder,
                $admin,
                $companyId
            );

            DB::commit();

            return to_route('admin.external_purchase_order_receives.index', $externalPurchaseOrder->id)->with(
                'success',
                'The specified partial received has been marked as completed successfully'
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

    public function addGrn(Request $request, int $externalPurchaseOrderReceiveId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $adminQueries = resolve(AdminQueries::class);
        $admin = $adminQueries->loadEmployee($admin);

        $userType = ModelMapping::getCaseName($admin::class);

        $externalPurchaseOrderReceive = $this->externalPurchaseOrderReceiveQueries->getById(
            $externalPurchaseOrderReceiveId
        );

        /** @var ExternalPurchaseOrder $externalPurchaseOrder */
        $externalPurchaseOrder = $externalPurchaseOrderReceive->externalPurchaseOrder;

        $this->externalPurchaseOrderReceiveQueries->updateIsGrn($externalPurchaseOrderReceive);

        externalPurchaseOrderPartialReceiveJob::dispatch(
            $externalPurchaseOrderReceiveId,
            $companyId,
            $admin->id,
            $userType
        );

        return to_route('admin.external_purchase_order_receives.index', $externalPurchaseOrder->id)->with(
            'success',
            'The specified partial received has been Goods Received Note add successfully'
        );
    }

    public function exportExternalPurchaseOrderPartialReceiveItems(
        int $partialReceiveId,
        string $fileName
    ): BinaryFileResponse {
        $externalPurchaseOrderPartialReceiveItemQueries = resolve(
            ExternalPurchaseOrderPartialReceiveItemQueries::class
        );
        $externalPurchaseOrderReceiveItems = $externalPurchaseOrderPartialReceiveItemQueries->getByExternalPurchaseOrderPartialReceiveId(
            $partialReceiveId,
        );

        return Excel::download(
            new ExternalPurchaseOrderPartialReceiveItemExport($externalPurchaseOrderReceiveItems),
            $fileName
        );
    }

    public function print(int $partialReceiveId): string
    {
        $externalPurchaseOrderPartialReceivePrintService = resolve(
            ExternalPurchaseOrderPartialReceivePrintService::class
        );

        return $externalPurchaseOrderPartialReceivePrintService->print($partialReceiveId);
    }

    public function markAsCancel(Request $request, int $partialReceiveId): RedirectResponse
    {
        $purchaseOrderPartialReceive = $this->externalPurchaseOrderReceiveQueries->getById($partialReceiveId);
        $externalPurchaseOrderPartialReceiveItemService = resolve(
            ExternalPurchaseOrderPartialReceiveItemService::class
        );

        DB::beginTransaction();

        try {
            $externalPurchaseOrderPartialReceiveItemService->markAsCancel(
                $purchaseOrderPartialReceive,
                $request->status_id
            );

            DB::commit();

            return to_route(
                'admin.external_purchase_order_receives.index',
                $purchaseOrderPartialReceive->externalPurchaseOrder?->id
            )->with('success', 'Partial receive canceled successfully');
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
