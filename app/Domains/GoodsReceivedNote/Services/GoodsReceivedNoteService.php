<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Services;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\Inventory\Services\GoodsReceivedNoteInventoryService;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Foundation\Auth\User;

class GoodsReceivedNoteService
{
    public function generateGrnReference(
        GoodsReceivedNoteQueries $goodsReceivedNoteQueries,
        int $companyId
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $companyGrnFormat = $companyQueries->getGrnFormat($companyId);

        return $goodsReceivedNoteQueries->generateGrnReference($companyGrnFormat, $companyId);
    }

    public function addProductAndInventory(
        GoodsReceivedNote $goodsReceivedNote,
        array $uploadedProduct,
        Product $product,
        User $user,
        int $companyId,
        ?UnitOfMeasureDerivative $derivative
    ): void {
        $batchQueries = resolve(BatchQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $goodsReceivedNoteInventoryService = resolve(GoodsReceivedNoteInventoryService::class);

        $batchId = null;
        $derivativeId = null;
        $currentDerivateRatio = null;
        $serialNumberId = null;
        $serialNumber = isset($uploadedProduct['serial_number']) ? trim(
            (string) $uploadedProduct['serial_number']
        ) : null;
        $inputQuantity = $uploadedProduct['quantity'];
        $purchaseAmountId = $purchaseAmountQueries->addNewAndGetId($uploadedProduct);

        if (null !== $serialNumber) {
            $serialNumberQueries = resolve(SerialNumberQueries::class);
            $existsSoldSerialNumber = $serialNumberQueries->getByCompanyIdAndSerialNumberWithStatusSold(
                $companyId,
                $serialNumber
            );
            $serialNumberData = [
                'company_id' => $companyId,
                'product_id' => $product->id,
                'serial_number' => $serialNumber,
                'status' => $existsSoldSerialNumber ? SerialNumberStatus::SOLD->value : SerialNumberStatus::ACTIVE->value,
            ];
            $serialNumberId = $serialNumberQueries->updateOrCreate($serialNumberData);
        }

        if (config('app.product_variant')) {
            if ($product->masterProduct && $product->masterProduct->has_batch) {
                $batchId = $batchQueries->addNewAndGetId($uploadedProduct, $companyId, $product->id);
            }
        } elseif ($product->has_batch) {
            $batchId = $batchQueries->addNewAndGetId($uploadedProduct, $companyId, $product->id);
        }

        if ($derivative instanceof UnitOfMeasureDerivative && array_key_exists(
            'derivative_name',
            $uploadedProduct
        ) && $uploadedProduct['derivative_name']) {
            $uploadedProduct['quantity'] = CommonFunctions::numberFormat(
                (float) ($inputQuantity / $derivative->ratio)
            );
            $derivativeId = $derivative->id;
            $currentDerivateRatio = (float) $derivative->ratio;
        }

        $goodsReceivedNoteProduct = $goodsReceivedNoteProductQueries->addNew(
            (float) $uploadedProduct['quantity'],
            $goodsReceivedNote->id,
            $product->id,
            $batchId,
            $purchaseAmountId,
            $derivativeId,
            (float) $inputQuantity,
            $currentDerivateRatio,
            $serialNumberId,
        );

        $goodsReceivedNoteInventoryService->addInventory(
            $goodsReceivedNoteProduct,
            $user,
            $goodsReceivedNote->location_id,
            $product->id,
            $batchId,
            $purchaseAmountId,
            $serialNumberId,
        );
    }

    public function rollbackInventory(GoodsReceivedNote $goodsReceivedNote, User $user, string $remarks): void
    {
        $goodsReceivedNoteInventoryService = resolve(GoodsReceivedNoteInventoryService::class);
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        foreach ($goodsReceivedNote->goodsReceivedNoteProducts as $goodsReceivedNoteProduct) {
            $goodsReceivedNoteInventoryService->rollbackInventoryForGRNCancellation(
                $goodsReceivedNoteProduct,
                $user,
                $goodsReceivedNote->location_id,
            );
        }

        $goodsReceivedNoteQueries->markAsCancel($goodsReceivedNote, $remarks, $user);
    }

    public function checkGoodReceivedNoteProduct(GoodsReceivedNote $goodsReceivedNote): bool
    {
        $goodsReceivedNoteWithSerialNumbers = $goodsReceivedNote->goodsReceivedNoteProducts->whereNotNull(
            'serial_number_id'
        );
        foreach ($goodsReceivedNoteWithSerialNumbers as $goodsReceivedNoteProduct) {
            /** @var SerialNumber $serialNumber */
            $serialNumber = $goodsReceivedNoteProduct->serialNumber;
            if ($serialNumber->status !== SerialNumberStatus::ACTIVE->value) {
                return true;
            }
        }

        return false;
    }

    public function markAsDeleteStatus(GoodsReceivedNote $goodsReceivedNote): void
    {
        $serialNumberQueries = resolve(SerialNumberQueries::class);

        foreach ($goodsReceivedNote->goodsReceivedNoteProducts as $goodsReceivedNoteProduct) {
            if ($goodsReceivedNoteProduct->serial_number_id) {
                /** @var SerialNumber $serialNumber */
                $serialNumber = $goodsReceivedNoteProduct->serialNumber;
                $serialNumberQueries->setAsDeleteStatus($serialNumber);
            }
        }
    }
}
