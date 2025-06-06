<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Services;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\Inventory\Services\StockAdjustmentInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Models\Batch;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Foundation\Auth\User;

class StockAdjustmentService
{
    public function addItemAndInventory(
        StockAdjustment $stockAdjustment,
        array $uploadedProduct,
        Product $product,
        ?UnitOfMeasureDerivative $derivative,
        User $user,
        int $companyId
    ): void {
        $batchQueries = resolve(BatchQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $stockAdjustmentInventoryService = resolve(StockAdjustmentInventoryService::class);
        $warehouse = null;
        $store = null;

        if (strtoupper($uploadedProduct['location_type']) === LocationTypes::STORE->name) {
            $store = $locationQueries->getIdAndNameByName(
                (string) $uploadedProduct['location_name'],
                $companyId,
                LocationTypes::STORE->value,
            );
        }

        if (strtoupper($uploadedProduct['location_type']) === LocationTypes::WAREHOUSE->name) {
            $warehouse = $locationQueries->getIdAndNameByName(
                (string) $uploadedProduct['location_name'],
                $companyId,
                LocationTypes::WAREHOUSE->value,
            );
        }

        /** @var Location $location */
        $location = $store ?? $warehouse;

        $purchaseAmountId = $this->getPurchaseAmountId($uploadedProduct);

        $batchId = null;
        $derivativeId = null;
        $currentDerivateRatio = null;
        $inputQuantity = (float) $uploadedProduct['quantity'];

        /** @var ?Batch $batch */
        $batch = $batchQueries->getByNumber((string) $uploadedProduct['batch_number'], $companyId);

        if (config('app.product_variant')) {
            if ($product->masterProduct && $product->masterProduct->has_batch) {
                if ($batch instanceof Batch) {
                    $batchId = $batch->id;
                }

                if (! $batchId && (array_key_exists(
                    'batch_number',
                    $uploadedProduct
                ) && $uploadedProduct['batch_number'])) {
                    $batchId = $batchQueries->addNewAndGetId($uploadedProduct, $companyId, $product->id);
                }
            }
        } elseif ($product->has_batch) {
            if ($batch instanceof Batch) {
                $batchId = $batch->id;
            }

            if (! $batchId && (array_key_exists(
                'batch_number',
                $uploadedProduct
            ) && $uploadedProduct['batch_number'])) {
                $batchId = $batchQueries->addNewAndGetId($uploadedProduct, $companyId, $product->id);
            }
        }

        if ($derivative instanceof UnitOfMeasureDerivative && array_key_exists(
            'derivative_name',
            $uploadedProduct
        ) && $uploadedProduct['derivative_name']) {
            $uploadedProduct['quantity'] = CommonFunctions::numberFormat($inputQuantity / $derivative->ratio);
            $derivativeId = $derivative->id;
            $currentDerivateRatio = (float) $derivative->ratio;
        }

        $stockAdjustmentItem = $stockAdjustmentItemQueries->addNew(
            (float) $uploadedProduct['quantity'],
            $stockAdjustment->id,
            $product->id,
            $location->id,
            $derivativeId,
            $inputQuantity,
            $currentDerivateRatio,
            $purchaseAmountId,
            $batchId,
        );

        $stockAdjustmentInventoryService->updateInventory(
            $stockAdjustmentItem,
            $uploadedProduct,
            $user,
            $location->id,
            $product,
            $purchaseAmountId,
            $batchId,
        );
    }

    public function getPurchaseAmountId(array $uploadedProduct): int
    {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);

        if (! array_key_exists('fob', $uploadedProduct)) {
            return $purchaseAmountQueries->addBlankRecord();
        }

        return $purchaseAmountQueries->addNewAndGetId($uploadedProduct);
    }
}
