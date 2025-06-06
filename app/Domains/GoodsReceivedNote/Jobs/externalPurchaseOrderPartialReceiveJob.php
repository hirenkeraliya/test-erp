<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Jobs;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\ExternalPurchaseOrderReceive\ExternalPurchaseOrderReceiveQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNote\Services\GoodsReceivedNoteService;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\Inventory\Services\GoodsReceivedNoteInventoryService;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\ExternalPurchaseOrder;
use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderPartialReceive;
use App\Models\ExternalPurchaseOrderPartialReceiveItemBatch;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Product;
use App\Models\PurchasePlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class externalPurchaseOrderPartialReceiveJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $externalPurchaseOrderPartialReceiveId,
        private readonly int $companyId,
        private readonly int $userId,
        private readonly string $userType,
    ) {
    }

    public function handle(): void
    {
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $goodsReceivedNoteService = resolve(GoodsReceivedNoteService::class);

        $externalPurchaseOrderPartialReceive = $externalPurchaseOrderReceiveQueries->getById(
            $this->externalPurchaseOrderPartialReceiveId
        );

        /** @var ExternalPurchaseOrder $externalPurchaseOrder */
        $externalPurchaseOrder = $externalPurchaseOrderPartialReceive->externalPurchaseOrder;

        /** @var PurchasePlan $purchasePlan */
        $purchasePlan = $externalPurchaseOrder->purchasePlan;

        $grnReferenceNumber = $goodsReceivedNoteService->generateGrnReference(
            $goodsReceivedNoteQueries,
            $this->companyId
        );

        try {
            $goodsReceivedNote = $this->addGoodsReceivedNote(
                $purchasePlan,
                $grnReferenceNumber,
                $externalPurchaseOrderPartialReceive
            );

            $externalPurchaseOrderReceiveQueries->addGoodsReceivedNoteId(
                $externalPurchaseOrderPartialReceive->id,
                $goodsReceivedNote->id
            );

            $purchaseAmountId = $this->getPurchaseAmountId($externalPurchaseOrder);

            $this->addReceivedItemsToInventory(
                $externalPurchaseOrderPartialReceive,
                $goodsReceivedNote,
                $purchaseAmountId,
                $purchasePlan
            );
        } catch (Throwable $throwable) {
            Log::error('External Purchase order Partial Receive Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }

    public function addGoodsReceivedNote(
        PurchasePlan $purchasePlan,
        string $grnReferenceNumber,
        ExternalPurchaseOrderPartialReceive $externalPurchaseOrderPartialReceive
    ): GoodsReceivedNote {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        $goodsReceivedNoteData = [
            'company_id' => $this->companyId,
            'vendor_id' => $purchasePlan->vendor_id,
            'location_id' => $purchasePlan->location_id,
            'grn_reference' => $grnReferenceNumber,
            'notes' => $externalPurchaseOrderPartialReceive->notes,
            'created_by_type' => $this->userType,
            'created_by_id' => $this->userId,
        ];

        return $goodsReceivedNoteQueries->addNewForExternalPurchaseOrder($goodsReceivedNoteData);
    }

    public function getPurchaseAmountId(ExternalPurchaseOrder $externalPurchaseOrder): int
    {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);

        $purchaseAmountDetails = [
            'fob' => $externalPurchaseOrder->fob,
            'freight_charges' => $externalPurchaseOrder->freight_charges,
            'insurance_charges' => $externalPurchaseOrder->insurance_charges,
            'duty' => $externalPurchaseOrder->duty,
            'sst' => $externalPurchaseOrder->sst,
            'handling_charges' => $externalPurchaseOrder->handling_charges,
            'other_charges' => $externalPurchaseOrder->other_charges,
        ];

        return $purchaseAmountQueries->addNewAndGetId($purchaseAmountDetails);
    }

    public function addReceivedItemsToInventory(
        ExternalPurchaseOrderPartialReceive $externalPurchaseOrderPartialReceive,
        GoodsReceivedNote $goodsReceivedNote,
        int $purchaseAmountId,
        PurchasePlan $purchasePlan
    ): void {
        $productQueries = resolve(ProductQueries::class);

        foreach ($externalPurchaseOrderPartialReceive->items as $item) {
            /** @var ExternalPurchaseOrderItem $externalPurchaseOrderItem */
            $externalPurchaseOrderItem = $item->externalPurchaseOrderItem;

            /** @var Product $product */
            $product = $externalPurchaseOrderItem->product;

            /** @var Product $actualProduct */
            $actualProduct = $productQueries->getActiveInventoryProductByUpcForGRN(
                (string) $product->upc,
                $this->companyId
            );

            /** @var Collection $itemBatches */
            $itemBatches = $item->itemBatches;

            if ($itemBatches->isEmpty()) {
                $goodsReceivedNoteProduct = $this->addGoodReceivedNoteProduct(
                    (float) $item->quantity_received,
                    $goodsReceivedNote->id,
                    $actualProduct->id,
                    $purchaseAmountId,
                    $item->unit_of_measure_derivative_id
                );
                $this->addInventory(
                    $goodsReceivedNoteProduct,
                    $purchasePlan,
                    $actualProduct,
                    $purchaseAmountId,
                    $externalPurchaseOrderPartialReceive->received_date,
                    $item->id
                );
            } elseif ($actualProduct->has_batch) {
                foreach ($itemBatches as $itemBatch) {
                    $batchId = $this->getBatchId($itemBatch, $actualProduct);

                    $goodsReceivedNoteProduct = $this->addGoodReceivedNoteProduct(
                        (float) $itemBatch->quantity,
                        $goodsReceivedNote->id,
                        $actualProduct->id,
                        $purchaseAmountId,
                        $item->unit_of_measure_derivative_id,
                        $batchId,
                    );

                    $this->addInventory(
                        $goodsReceivedNoteProduct,
                        $purchasePlan,
                        $actualProduct,
                        $purchaseAmountId,
                        $externalPurchaseOrderPartialReceive->received_date,
                        $item->id,
                        $batchId,
                    );
                }
            }
        }
    }

    public function addGoodReceivedNoteProduct(
        float $quantityReceived,
        int $goodsReceivedNoteId,
        int $productId,
        int $purchaseAmountId,
        ?int $derivativeId,
        ?int $batchId = null
    ): GoodsReceivedNoteProduct {
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $currentDerivateRatio = null;
        $inputQuantity = $quantityReceived;
        if ($derivativeId) {
            $derivative = $unitOfMeasureDerivativeQueries->getByOnlyId($derivativeId);
            $currentDerivateRatio = (float) $derivative->ratio;
            $quantityReceived = CommonFunctions::numberFormat($inputQuantity / $derivative->ratio);
        }

        return $goodsReceivedNoteProductQueries->addNew(
            $quantityReceived,
            $goodsReceivedNoteId,
            $productId,
            $batchId,
            $purchaseAmountId,
            $derivativeId,
            $inputQuantity,
            $currentDerivateRatio,
            null,
        );
    }

    public function addInventory(
        GoodsReceivedNoteProduct $goodsReceivedNoteProduct,
        PurchasePlan $purchasePlan,
        Product $actualProduct,
        int $purchaseAmountId,
        string $happenedAt,
        int $itemId,
        ?int $batchId = null,
    ): void {
        $goodsReceivedNoteInventoryService = resolve(GoodsReceivedNoteInventoryService::class);

        $goodsReceivedNoteInventoryService->addInventoryForExternalPurchaseOrder(
            $goodsReceivedNoteProduct,
            $purchasePlan->location_id,
            $actualProduct->id,
            $batchId,
            $purchaseAmountId,
            $this->userId,
            $this->userType,
            $happenedAt,
            $itemId
        );
    }

    public function getBatchId(ExternalPurchaseOrderPartialReceiveItemBatch $itemBatch, Product $actualProduct): int
    {
        $batchQueries = resolve(BatchQueries::class);

        $batchDetails = [
            'batch_number' => $itemBatch->batch_number,
            'batch_expiry_date' => $itemBatch->expiry_date,
            'batch_notes' => $itemBatch->notes,
            'batch_external_id' => null,
        ];

        return $batchQueries->addNewAndGetId($batchDetails, $this->companyId, $actualProduct->id);
    }
}
