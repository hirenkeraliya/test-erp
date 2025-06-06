<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Inventory\Services\PurchaseOrderInventoryService;
use App\Domains\PurchaseOrder\DataObjects\PurchaseOrderApiData;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\Jobs\SaleOrderApproveJob;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurchaseOrderController extends Controller
{
    public function store(Request $request, PurchaseOrderApiData $purchaseOrderApiData): array
    {
        $purchaseOrderService = resolve(PurchaseOrderService::class);
        [$products, $purchaseOrderData] = $purchaseOrderService->prepareExternalPurchaseOrder(
            $purchaseOrderApiData->all(), $request->input('token')
        );

        DB::beginTransaction();

        try {
            $response = $purchaseOrderService->saveExternalPurchaseOrder($purchaseOrderData, $products);

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

    public function reject(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyIdWithRelation(
            $validateData['purchase_order_id'],
            $validateData['company_id']
        );

        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);

        DB::beginTransaction();

        try {
            $purchaseOrderTransactionQueries->addNew(
                $purchaseOrder->id,
                $purchaseOrder->status,
                Statuses::REJECTED->value,
                null,
                $validateData['external_username']
            );

            if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
                $purchaseOrderInventoryService = resolve(PurchaseOrderInventoryService::class);
                $purchaseOrderInventoryService->revertReservedStockForPurchaseOrderRecord($purchaseOrder);
            }

            $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::REJECTED->value);

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

    public function cancel(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyId(
            $validateData['purchase_order_id'],
            $validateData['company_id']
        );

        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);

        DB::beginTransaction();

        try {
            $purchaseOrderTransactionQueries->addNew(
                $purchaseOrder->id,
                $purchaseOrder->status,
                Statuses::CANCELLED->value,
                null,
                $validateData['external_username']
            );

            $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::CANCELLED->value);

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

    public function closed(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdAndCompanyId(
            $validateData['purchase_order_id'],
            $validateData['company_id']
        );

        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);

        DB::beginTransaction();

        try {
            $purchaseOrderTransactionQueries->addNew(
                $purchaseOrder->id,
                $purchaseOrder->status,
                Statuses::CLOSED->value,
                null,
                $validateData['external_username']
            );

            $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::CLOSED->value);

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

    public function autoApprove(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
        ]);

        SaleOrderApproveJob::dispatch($validateData['purchase_order_id'], $validateData['company_id'])->onQueue(
            config('horizon.default_queue_name')
        );
    }

    public function checkPurchaseOrderCancel(Request $request): array
    {
        $validateData = $request->validate([
            'purchase_order_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
        ]);

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return [
            'is_purchase_order_cancel' => $purchaseOrderQueries->isPurchaseOrderCancel(
                (int) $validateData['purchase_order_id'],
                (int) $validateData['company_id']
            ),
        ];
    }
}
