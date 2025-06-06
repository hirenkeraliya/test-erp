<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderInvoice\Services\PurchaseOrderInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurchaseOrderInvoiceController extends Controller
{
    public function paid(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_invoice_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
        ]);

        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $purchaseOrderInvoice = $purchaseOrderInvoiceQueries->getById(
            $validateData['purchase_order_invoice_id'],
            $validateData['company_id']
        );

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceQueries->updateInvoiceStatus($purchaseOrderInvoice, InvoiceStatuses::PAID->value);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order api' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function markAsReceived(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_invoice_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
        ]);

        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $purchaseOrderInvoice = $purchaseOrderInvoiceQueries->getById(
            $validateData['purchase_order_invoice_id'],
            $validateData['company_id']
        );

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceQueries->updateInvoiceStatus($purchaseOrderInvoice, InvoiceStatuses::RECEIVED->value);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order api' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function sent(Request $request): array
    {
        $validateData = $request->validate([
            'external_purchase_order_invoice_id' => ['required', 'integer'],
            'purchase_order_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'status' => ['required', 'integer'],
            'invoice_number' => ['required', 'string'],
            'purchase_order_fulfillments' => ['required', 'array'],
            'purchase_order_fulfillments.*.purchase_order_fulfillment_id' => ['required', 'integer'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        /** @var array $purchaseOrderFulfillmentData */
        $purchaseOrderFulfillmentData = $validateData['purchase_order_fulfillments'];
        $purchaseOrderFulfillmentIds = collect($purchaseOrderFulfillmentData)->pluck(
            'purchase_order_fulfillment_id'
        )->toArray();
        $purchaseOrderFulfillments = $purchaseOrderFulfillmentQueries->getByIds(
            $purchaseOrderFulfillmentIds,
            $validateData['company_id']
        );

        DB::beginTransaction();

        try {
            $purchaseOrderInvoiceService = resolve(PurchaseOrderInvoiceService::class);
            $response = $purchaseOrderInvoiceService->saveExternalInvoice(
                $validateData,
                $purchaseOrderFulfillments,
            );

            DB::commit();

            return $response;
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order api' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }
}
