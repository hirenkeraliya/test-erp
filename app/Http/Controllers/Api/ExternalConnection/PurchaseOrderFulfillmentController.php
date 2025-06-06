<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Inventory\Services\PurchaseOrderTransitStockService;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentApiData;
use App\Domains\PurchaseOrderFulfillment\DataObjects\PurchaseOrderFulfillmentUpdateApiData;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillment\Services\PurchaseOrderFulfillmentService;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderFulfillmentTransaction\PurchaseOrderFulfillmentTransactionQueries;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderFulfillmentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurchaseOrderFulfillmentController extends Controller
{
    public function store(PurchaseOrderFulfillmentApiData $purchaseOrderFulfillmentApiData): array
    {
        $purchaseOrderFulfillData = $purchaseOrderFulfillmentApiData->all();
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdWithItems(
            $purchaseOrderFulfillData['purchase_order_id'],
            $purchaseOrderFulfillData['company_id']
        );
        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $products = $purchaseOrderService->prepareExternalFulfillment($purchaseOrderFulfillData);

        $purchaseOrderService->checkProductNotExist($products, $purchaseOrderFulfillData);

        DB::beginTransaction();

        try {
            if ($purchaseOrder->status === Statuses::APPROVED->value) {
                $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
                $purchaseOrderTransactionQueries->addNew(
                    $purchaseOrder->id,
                    $purchaseOrder->status,
                    Statuses::PARTIAL_FULFILLMENT->value,
                    null,
                    $purchaseOrderFulfillData['external_username']
                );

                $purchaseOrderQueries->updateStatus($purchaseOrder, Statuses::PARTIAL_FULFILLMENT->value);
            }

            $response = $purchaseOrderService->saveExternalFulfillment(
                $purchaseOrderFulfillData,
                $products,
                $purchaseOrder
            );

            $purchaseOrderService->markAsFulfillmentCompletedPurchaseOrder(
                $purchaseOrder,
                null,
                $purchaseOrderFulfillData['external_username']
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

    public function discrepancy(PurchaseOrderFulfillmentUpdateApiData $purchaseOrderFulfillmentUpdateApiData): array
    {
        $purchaseOrderFulfillData = $purchaseOrderFulfillmentUpdateApiData->all();
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdWithRelation(
            $purchaseOrderFulfillmentUpdateApiData->purchase_order_fulfillment_id,
            $purchaseOrderFulfillmentUpdateApiData->company_id,
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $products = $purchaseOrderService->prepareExternalFulfillment($purchaseOrderFulfillData);

        $purchaseOrderService->checkProductNotExist($products, $purchaseOrderFulfillData);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
            $purchaseOrderFulfillmentTransactionQueries->addNew(
                $purchaseOrderFulfillment->id,
                $purchaseOrderFulfillment->status,
                FulfillmentStatuses::DISCREPANCY->value,
                null,
                $purchaseOrderFulfillData['external_username']
            );

            $purchaseOrderFulfillmentQueries->updateStatus(
                $purchaseOrderFulfillment,
                FulfillmentStatuses::DISCREPANCY->value
            );

            $response = $purchaseOrderService->updateExternalFulfillmentDiscrepancy(
                $purchaseOrderFulfillData,
                $purchaseOrderFulfillment,
                $products
            );

            DB::commit();

            return $response;
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function closed(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_fulfillment_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdAndCompanyId(
            $validateData['purchase_order_fulfillment_id'],
            $validateData['company_id']
        );

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdWithItemsAndFulfillment(
            $purchaseOrderFulfillment->purchase_order_id,
            $validateData['company_id']
        );

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
            $purchaseOrderFulfillmentTransactionQueries->addNew(
                $purchaseOrderFulfillment->id,
                $purchaseOrderFulfillment->status,
                FulfillmentStatuses::CLOSED->value,
                null,
                $validateData['external_username']
            );

            $purchaseOrderFulfillmentQueries->updateStatus(
                $purchaseOrderFulfillment,
                FulfillmentStatuses::CLOSED->value
            );

            $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
            $purchaseOrderFulfillmentItemQueries->setReceivedQuantitySameAsQuantity(
                $purchaseOrderFulfillment->id,
                $validateData['company_id']
            );

            DB::commit();

            $purchaseOrderService = resolve(PurchaseOrderService::class);
            $purchaseOrderService->closePurchaseOrder($purchaseOrder);
        } catch (Throwable $throwable) {
            Log::error([
                'purchase order api' => $throwable->getMessage(),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(417, 'An error occurred. Please try again.');
        }
    }

    public function getDeliveryOrderStatus(Request $request): array
    {
        $validateData = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getById((int) $validateData['id']);

        if ((int) $purchaseOrderFulfillment->status === FulfillmentStatuses::DISCREPANCY->value) {
            $purchaseOrderFulfillment->status = FulfillmentStatuses::RECEIVED->value;
            $purchaseOrderFulfillment->save();
        }

        return [
            'status' => $purchaseOrderFulfillment->status,
        ];
    }

    public function closedDiscrepancy(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_fulfillment_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.received_quantity' => ['nullable', 'numeric', 'min:0.00'],
            'items.*.batch_details' => ['nullable', 'array'],
            'items.*.remarks' => ['nullable', 'string'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdWithRelationForEdit(
            $validateData['purchase_order_fulfillment_id'],
            $validateData['company_id']
        );

        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
        $purchaseOrderFulfillmentTransaction = $purchaseOrderFulfillmentTransactionQueries->getByPurchaseOrderFulfillmentId(
            $purchaseOrderFulfillment->id,
            FulfillmentStatuses::DISCREPANCY->value
        );

        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrder = $purchaseOrderQueries->getByIdWithItemsAndFulfillment(
            $purchaseOrderFulfillment->purchase_order_id,
            $validateData['company_id']
        );

        $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
            $purchaseOrderFulfillmentTransactionQueries->addNew(
                $purchaseOrderFulfillment->id,
                $purchaseOrderFulfillment->status,
                FulfillmentStatuses::CLOSED->value,
                null,
                $validateData['external_username']
            );

            $purchaseOrderFulfillmentQueries->updateStatus(
                $purchaseOrderFulfillment,
                FulfillmentStatuses::CLOSED->value,
            );

            $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
            $purchaseOrderFulfillmentService->closeExternalDiscrepancy(
                $purchaseOrderFulfillment,
                $validateData,
                $purchaseOrderFulfillmentTransaction,
            );

            DB::commit();

            $purchaseOrderService = resolve(PurchaseOrderService::class);
            $purchaseOrderService->closePurchaseOrder($purchaseOrder);
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
            'purchase_order_fulfillment_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdAndCompanyId(
            $validateData['purchase_order_fulfillment_id'],
            $validateData['company_id']
        );

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
            $purchaseOrderFulfillmentTransactionQueries->addNew(
                $purchaseOrderFulfillment->id,
                $purchaseOrderFulfillment->status,
                FulfillmentStatuses::RECEIVED->value,
                null,
                $validateData['external_username']
            );

            $purchaseOrderFulfillmentQueries->updateStatus(
                $purchaseOrderFulfillment,
                FulfillmentStatuses::RECEIVED->value
            );

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

    public function markAsShift(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_fulfillment_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdAndCompanyId(
            $validateData['purchase_order_fulfillment_id'],
            $validateData['company_id']
        );

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);
            $purchaseOrderFulfillmentTransactionQueries->addNew(
                $purchaseOrderFulfillment->id,
                $purchaseOrderFulfillment->status,
                FulfillmentStatuses::SHIPPED->value,
                null,
                $validateData['external_username']
            );

            $purchaseOrderFulfillmentQueries->updateStatus(
                $purchaseOrderFulfillment,
                FulfillmentStatuses::SHIPPED->value
            );

            $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->loadRelations($purchaseOrderFulfillment);
            $purchaseOrderTransitStockService = resolve(PurchaseOrderTransitStockService::class);
            $purchaseOrderTransitStockService->addTransitStock($purchaseOrderFulfillment);

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

    public function markAsCanceled(Request $request): void
    {
        $validateData = $request->validate([
            'purchase_order_fulfillment_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
            'external_username' => ['nullable', 'string'],
        ]);

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdForCancelDeliveryOrder(
            $validateData['purchase_order_fulfillment_id'],
            $validateData['company_id']
        );

        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);

        /** @var PurchaseOrderFulfillmentTransaction $purchaseOrderFulfillmentTransaction */
        $purchaseOrderFulfillmentTransaction = $purchaseOrderFulfillmentTransactionQueries->getByPurchaseOrderFulfillmentIdAndNewStatuses(
            $purchaseOrderFulfillment->id,
            [FulfillmentStatuses::SHIPPED->value, FulfillmentStatuses::OPEN->value]
        );

        DB::beginTransaction();

        try {
            $purchaseOrderFulfillmentService = resolve(PurchaseOrderFulfillmentService::class);
            $purchaseOrderFulfillmentService->cancelExternalDeliveryOrder(
                $purchaseOrderFulfillment,
                $purchaseOrderFulfillmentTransaction,
                $validateData['external_username']
            );
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
}
