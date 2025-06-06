<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderInvoice\DataObjects\PurchaseOrderInvoiceData;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderInvoice\Services\PurchaseOrderInvoiceService;
use App\Domains\PurchaseOrderInvoice\Services\PurchaseOrderPrintInvoiceService;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PurchaseOrderInvoiceController extends Controller
{
    public function __construct(
        protected PurchaseOrderInvoiceQueries $purchaseOrderInvoiceQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('purchase_order_invoices/Index', [
            'statuses' => InvoiceStatuses::getStatuses(),
            'status' => InvoiceStatuses::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('purchase_order_invoice'),
        ]);
    }

    public function fetchPurchaseOrderInvoices(Request $request): array
    {
        $filterData = $this->getPreparedFilters($request);

        $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);

        return $purchaseOrderInvoiceService->fetchPurchaseOrderInvoices(
            $filterData,
            session('store_manager_selected_location_company_id')
        );
    }

    public function create(): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrders = $purchaseOrderQueries->getPurchaseOrderNumberByLocation(
            $companyId,
            session('store_manager_selected_location_id'),
        );

        return Inertia::render('purchase_order_invoices/Manage', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function store(PurchaseOrderInvoiceData $purchaseOrderInvoiceData): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdLocationAndStatusAndOrderType(
            $companyId,
            $purchaseOrderInvoiceData->purchase_order_id,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillments = $purchaseOrderFulfillmentQueries->getByIds(
            (array) $purchaseOrderInvoiceData->fulfillment_ids,
            $companyId
        );

        if ($purchaseOrderFulfillments->isEmpty()) {
            abort(417, 'Please select at least one Delivery Order to create an invoice.');
        }

        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            if ($purchaseOrderFulfillment->purchase_order_invoice_id) {
                abort(
                    417,
                    'Delivery Order with Delivery Order Number "' . $purchaseOrderFulfillment->delivery_order_number . '" already included in another invoice.'
                );
            }
        }

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);
            $purchaseOrderInvoiceService->storePurchaseOrderInvoice(
                $purchaseOrderFulfillments,
                $companyId,
                $purchaseOrder->getKey(),
                $purchaseOrder->location_id,
            );

            DB::commit();

            return to_route('store_manager.purchase_order_invoices.index')->with(
                'success',
                'The invoice has been added successfully.'
            );
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order invoice' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function edit(int $purchaseOrderInvoiceId, int $purchaseOrderId): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderInvoice = $this->purchaseOrderInvoiceQueries->getByIdAndLocation(
            $purchaseOrderInvoiceId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderInvoice->status !== InvoiceStatuses::DRAFT->value) {
            throw new RedirectWithErrorException(
                'store_manager.purchase_order_invoices.index',
                'The purchase order invoice is locked for editing as it is currently not in draft status.'
            );
        }

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillments = $purchaseOrderFulfillmentQueries->getFulfillmentDetailsByPurchaseOrderId(
            $purchaseOrderInvoiceId,
            $purchaseOrderId,
            $companyId
        );

        return Inertia::render('purchase_order_invoices/Edit', [
            'purchaseOrderFulfillments' => $purchaseOrderFulfillments,
            'purchaseOrderInvoiceId' => $purchaseOrderInvoiceId,
        ]);
    }

    public function updateInvoiceId(int $purchaseOrderFulfillmentId, int $purchaseOrderInvoiceId): RedirectResponse
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdLocationAndCompanyId(
            $purchaseOrderFulfillmentId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
        );
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getById($purchaseOrderFulfillmentId);

        if ($purchaseOrderFulfillment->purchase_order_invoice_id) {
            abort(417, 'Delivery Order already included in another invoice.');
        }

        $purchaseOrderFulfillmentQueries->updateInvoiceId($purchaseOrderFulfillment, $purchaseOrderInvoiceId);

        return to_route(
            'store_manager.purchase_order_invoices.edit',
            [$purchaseOrderInvoiceId, $purchaseOrderFulfillment->purchase_order_id]
        )->with('success', 'Successfully added the delivery order to the invoice');
    }

    public function removeInvoiceId(int $purchaseOrderFulfillmentId, int $purchaseOrderInvoiceId): RedirectResponse
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdLocationAndCompanyId(
            $purchaseOrderFulfillmentId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
        );
        $purchaseOrderFulfillmentQueries->updateRemoveInvoiceId($purchaseOrderFulfillment);

        return to_route(
            'store_manager.purchase_order_invoices.edit',
            [$purchaseOrderInvoiceId, $purchaseOrderFulfillment->purchase_order_id]
        )->with('success', 'Successfully remove the delivery order to the invoice');
    }

    public function cancel(Request $request, int $purchaseOrderInvoiceId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoice = $this->purchaseOrderInvoiceQueries->getByIdAndLocation(
            $purchaseOrderInvoiceId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        $purchaseOrderFulfillments = $purchaseOrderFulfillmentQueries->getPurchaseOrderFulfillmentByInvoiceId(
            $purchaseOrderInvoiceId
        );

        if ($purchaseOrderInvoice->status !== InvoiceStatuses::DRAFT->value) {
            abort(
                417,
                'Cancellation of the purchase order invoice is not possible at this moment, as it is not in a draft status.'
            );
        }

        /** @var User $user */
        $user = $request->user();

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);
            $purchaseOrderInvoiceService->purchaseOrderInvoiceCancel(
                $purchaseOrderInvoice,
                $purchaseOrderFulfillments,
                $user
            );

            DB::commit();

            return to_route('store_manager.purchase_order_invoices.index')->with(
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

    public function sent(Request $request, int $purchaseOrderInvoiceId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderInvoice = $this->purchaseOrderInvoiceQueries->getByIdAndLocationForSent(
            $purchaseOrderInvoiceId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if ($purchaseOrderInvoice->status !== InvoiceStatuses::DRAFT->value) {
            abort(
                417,
                'Sending the purchase order invoice is currently unavailable since it is not in a draft status.'
            );
        }

        if ($purchaseOrderInvoice->fulfillments->isEmpty()) {
            abort(417, 'At list one item select for send.');
        }

        /** @var User $user */
        $user = $request->user();

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);
            $purchaseOrderInvoiceService->purchaseOrderInvoiceSent($purchaseOrderInvoice, $user, $companyId);

            DB::commit();

            return to_route('store_manager.purchase_order_invoices.index')->with(
                'success',
                'Invoice has been marked as Sent successfully'
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

    public function paid(Request $request, int $purchaseOrderInvoiceId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderInvoice = $this->purchaseOrderInvoiceQueries->getByIdAndLocationForPaid(
            $purchaseOrderInvoiceId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if (($purchaseOrderInvoice->status !== InvoiceStatuses::RECEIVED->value) || (null !== $purchaseOrderInvoice->created_by_company_id)) {
            abort(
                417,
                'Paying the purchase order invoice is not possible at the moment as it has not been marked as received.'
            );
        }

        /** @var User $user */
        $user = $request->user();

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);
            $purchaseOrderInvoiceService->purchaseOrderInvoicePaid($purchaseOrderInvoice, $user);

            DB::commit();

            return to_route('store_manager.purchase_order_invoices.index')->with(
                'success',
                'Invoice has been marked as paid successfully'
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

    public function markAsReceived(Request $request, int $purchaseOrderInvoiceId): RedirectResponse
    {
        $companyId = session('store_manager_selected_location_company_id');

        $purchaseOrderInvoice = $this->purchaseOrderInvoiceQueries->getByIdAndLocationForPaid(
            $purchaseOrderInvoiceId,
            $companyId,
            session('store_manager_selected_location_id'),
        );

        if (($purchaseOrderInvoice->status !== InvoiceStatuses::SENT->value) || (null !== $purchaseOrderInvoice->created_by_company_id)) {
            abort(
                417,
                'Marking the purchase order invoice as received is not possible until it has not been marked as sent.'
            );
        }

        /** @var User $user */
        $user = $request->user();

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);
            $purchaseOrderInvoiceService->purchaseOrderInvoiceReceived($purchaseOrderInvoice, $user);

            DB::commit();

            return to_route('store_manager.purchase_order_invoices.index')->with(
                'success',
                'Invoice has been marked as received successfully'
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

    public function print(int $purchaseOrderInvoiceId): string
    {
        $purchaseOrderPrintInvoiceService = resolve(PurchaseOrderPrintInvoiceService::class);

        return $purchaseOrderPrintInvoiceService->printInvoice(
            $purchaseOrderInvoiceId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
        );
    }

    public function fulfillmentDetails(int $purchaseOrderId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillments = $purchaseOrderFulfillmentQueries->getFulfillmentDetailsByOrderNumber(
            $purchaseOrderId,
            $companyId
        );

        return [
            'purchaseOrderFulfillments' => $purchaseOrderFulfillments,
        ];
    }

    public function refreshPrice(int $purchaseOrderId): void
    {
        DB::beginTransaction();
        try {
            $purchaseOrderItemsQueries = resolve(PurchaseOrderItemQueries::class);
            $purchaseOrderItemsQueries->updatePurchaseCostOfDraftStatus($purchaseOrderId);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error([
                'Refresh price' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();
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
            'location_id' => session('store_manager_selected_location_id'),
        ];
    }
}
