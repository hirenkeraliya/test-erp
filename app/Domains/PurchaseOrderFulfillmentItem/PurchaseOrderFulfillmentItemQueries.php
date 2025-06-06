<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItemBatch\PurchaseOrderFulfillmentItemBatchQueries;
use App\Domains\PurchaseOrderFulfillmentItemTransaction\PurchaseOrderFulfillmentItemTransactionQueries;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderItem;
use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PurchaseOrderFulfillmentItemQueries
{
    public function addNew(array $purchaseOrderFulfillmentItemData): PurchaseOrderFulfillmentItem
    {
        return PurchaseOrderFulfillmentItem::create($purchaseOrderFulfillmentItemData);
    }

    public function getByPurchaseOrderFulfillmentId(int $purchaseOrderFulfillmentId, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $purchaseOrderFulfillmentItemTransactionQueries = resolve(
            PurchaseOrderFulfillmentItemTransactionQueries::class
        );
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillmentItem::query()
                ->select(
                    'id',
                    'purchase_order_fulfillment_id',
                    'purchase_order_item_id',
                    'product_id',
                    'transfer_quantity',
                    'received_quantity',
                    'package_type_id',
                    'is_extra_item',
                    'discrepancy_type',
                    'remarks',
                )
                ->with(
                    'product:' . $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                    'itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
                    'transactions:' . $purchaseOrderFulfillmentItemTransactionQueries->getBasicColumns(),
                    'media:' . $mediaQueries->getBasicColumnNames(),
                    'partialReceivedItems:' . $partiallyReceiveFulfillmentItemQueries->getBasicColumnNames(),
                    'purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'purchaseOrderItem.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'purchaseOrderItem.derivative.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                )
                ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
                ->whereHas('purchaseOrderFulfillment', function ($query) use ($purchaseOrderQueries, $companyId): void {
                    $query->select('id', 'purchase_order_id')
                        ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
                })
                ->get();
        }

        return PurchaseOrderFulfillmentItem::query()
            ->select(
                'id',
                'purchase_order_fulfillment_id',
                'purchase_order_item_id',
                'product_id',
                'transfer_quantity',
                'received_quantity',
                'package_type_id',
                'is_extra_item',
                'discrepancy_type',
                'remarks',
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
                'transactions:' . $purchaseOrderFulfillmentItemTransactionQueries->getBasicColumns(),
                'media:' . $mediaQueries->getBasicColumnNames(),
                'partialReceivedItems:' . $partiallyReceiveFulfillmentItemQueries->getBasicColumnNames(),
                'purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrderItem.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'purchaseOrderItem.derivative.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            )
            ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
            ->whereHas('purchaseOrderFulfillment', function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->select('id', 'purchase_order_id')
                    ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->get();
    }

    public function getByPurchaseOrderFulfillmentIdAndLocation(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        int $locationId,
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $purchaseOrderFulfillmentItemTransactionQueries = resolve(
            PurchaseOrderFulfillmentItemTransactionQueries::class
        );
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillmentItem::query()
                ->select(
                    'id',
                    'purchase_order_fulfillment_id',
                    'purchase_order_item_id',
                    'product_id',
                    'transfer_quantity',
                    'received_quantity',
                    'package_type_id',
                    'is_extra_item',
                    'discrepancy_type',
                    'remarks',
                )
                ->with(
                    'product:' . $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                    'itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
                    'transactions:' . $purchaseOrderFulfillmentItemTransactionQueries->getBasicColumns(),
                    'media:' . $mediaQueries->getBasicColumnNames(),
                )
                ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
                ->whereHas('purchaseOrderFulfillment', function ($query) use (
                    $purchaseOrderQueries,
                    $companyId,
                    $locationId
                ): void {
                    $query->select('id', 'purchase_order_id')
                        ->whereHas(
                            'purchaseOrder',
                            $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId)
                        );
                })
                ->get();
        }

        return PurchaseOrderFulfillmentItem::query()
            ->select(
                'id',
                'purchase_order_fulfillment_id',
                'purchase_order_item_id',
                'product_id',
                'transfer_quantity',
                'received_quantity',
                'package_type_id',
                'is_extra_item',
                'discrepancy_type',
                'remarks',
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
                'transactions:' . $purchaseOrderFulfillmentItemTransactionQueries->getBasicColumns(),
                'media:' . $mediaQueries->getBasicColumnNames(),
            )
            ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
            ->whereHas('purchaseOrderFulfillment', function ($query) use (
                $purchaseOrderQueries,
                $companyId,
                $locationId
            ): void {
                $query->select('id', 'purchase_order_id')
                    ->whereHas(
                        'purchaseOrder',
                        $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId)
                    );
            })
            ->get();
    }

    public function getBasicColumn(): string
    {
        return 'id,purchase_order_fulfillment_id,purchase_order_item_id,product_id,transfer_quantity,received_quantity,remarks,external_purchase_order_fulfillment_item_id,package_type_id,package_quantity,package_total_quantity,is_extra_item,discrepancy_type';
    }

    public function getBasicColumnForReport(): string
    {
        return 'id,purchase_order_fulfillment_id,purchase_order_item_id';
    }

    public function getBasicColumnForPartialReceive(): string
    {
        return 'id,purchase_order_fulfillment_id,purchase_order_item_id,product_id,transfer_quantity,received_quantity';
    }

    public function getById(int $purchaseOrderFulfillmentItemId): PurchaseOrderFulfillmentItem
    {
        return PurchaseOrderFulfillmentItem::query()
            ->select(
                'id',
                'purchase_order_fulfillment_id',
                'purchase_order_item_id',
                'product_id',
                'transfer_quantity',
                'received_quantity',
                'remarks',
                'external_purchase_order_fulfillment_item_id'
            )
            ->findOrFail($purchaseOrderFulfillmentItemId);
    }

    public function getByIdWithProductAndPurchaseOrder(
        int $purchaseOrderFulfillmentItemId
    ): PurchaseOrderFulfillmentItem {
        $purchaseOrderFulfillmentQueries = new PurchaseOrderFulfillmentQueries();
        $productQueries = new ProductQueries();
        $purchaseOrderQueries = new PurchaseOrderQueries();
        $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
        $unitOfMeasureDerivativeQueries = new UnitOfMeasureDerivativeQueries();

        return PurchaseOrderFulfillmentItem::query()
            ->select('id', 'purchase_order_fulfillment_id', 'purchase_order_item_id', 'product_id', 'transfer_quantity')
            ->with([
                'purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getExternalLocationId(),
                'purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrderItem.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'product:' . $productQueries->getIdAndUpc(),
            ])
            ->findOrFail($purchaseOrderFulfillmentItemId);
    }

    public function getByIdWithProductAndPurchaseOrderAndPurchaseOrderItem(
        int $purchaseOrderFulfillmentItemId
    ): PurchaseOrderFulfillmentItem {
        $purchaseOrderFulfillmentQueries = new PurchaseOrderFulfillmentQueries();
        $productQueries = new ProductQueries();
        $purchaseOrderQueries = new PurchaseOrderQueries();
        $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $unitOfMeasureDerivativeQueries = new UnitOfMeasureDerivativeQueries();
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillmentItem::query()
                ->select(
                    'id',
                    'purchase_order_fulfillment_id',
                    'purchase_order_item_id',
                    'product_id',
                    'transfer_quantity',
                    'received_quantity'
                )
                ->with([
                    'purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                    'purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getExternalLocationId(),
                    'product:' . $productQueries->getIdAndUpcAndUnitOfMeasure(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                ])
                ->findOrFail($purchaseOrderFulfillmentItemId);
        }

        return PurchaseOrderFulfillmentItem::query()
            ->select(
                'id',
                'purchase_order_fulfillment_id',
                'purchase_order_item_id',
                'product_id',
                'transfer_quantity',
                'received_quantity'
            )
            ->with([
                'purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getExternalLocationId(),
                'product:' . $productQueries->getIdAndUpcAndUnitOfMeasure(),
                'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($purchaseOrderFulfillmentItemId);
    }

    public function uploadDiscrepancyProof(
        array $validatedData,
        int $purchaseOrderFulfillmentItemId
    ): PurchaseOrderFulfillmentItem {
        $purchaseOrderFulfillmentItem = $this->getById($purchaseOrderFulfillmentItemId);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $validatedData['discrepancy_proof'];

        $mediaQueries = resolve(MediaQueries::class);

        $purchaseOrderFulfillmentItem->addMedia($uploadedFile)->toMediaCollection('discrepancy_proof');
        $purchaseOrderFulfillmentItem->load('media:' . $mediaQueries->getBasicColumnNames());

        return $purchaseOrderFulfillmentItem;
    }

    public function removeDiscrepancyProof(int $purchaseOrderFulfillmentItemId): void
    {
        $purchaseOrderFulfillmentItem = $this->getById($purchaseOrderFulfillmentItemId);

        $purchaseOrderFulfillmentItem->clearMediaCollection('discrepancy_proof');
    }

    public function updateExternalId(
        int $purchaseOrderFulfillmentItemId,
        int $externalPurchaseOrderFulfillmentItemId
    ): void {
        $purchaseOrderFulfillmentItem = $this->getById($purchaseOrderFulfillmentItemId);
        $purchaseOrderFulfillmentItem->external_purchase_order_fulfillment_item_id = $externalPurchaseOrderFulfillmentItemId;
        $purchaseOrderFulfillmentItem->save();
    }

    public function setReceivedQuantitySameAsQuantity(int $purchaseOrderFulfillmentId, int $companyId): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrderFulfillmentItems = PurchaseOrderFulfillmentItem::query()
            ->select('id', 'received_quantity', 'transfer_quantity')
            ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
            ->where('is_extra_item', false)
            ->whereHas('purchaseOrderFulfillment', function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->select('id', 'purchase_order_id')
                    ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->get();

        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $purchaseOrderFulfillmentItem->update([
                'received_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity,
                'discrepancy_type' => null,
            ]);

            $purchaseOrderFulfillmentItem->clearMediaCollection('discrepancy_proof');
        }
    }

    public function updateReceivedQuantityAndDiscrepancyStatusById(
        array $purchaseOrderFulfillmentItemDetails,
        int $purchaseOrderFulfillmentId,
        int $companyId
    ): void {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrderFulfillmentItem = PurchaseOrderFulfillmentItem::query()
            ->select('id', 'purchase_order_fulfillment_id', 'received_quantity', 'discrepancy_type')
            ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
            ->whereHas('purchaseOrderFulfillment', function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->select('id', 'purchase_order_id')
                    ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->findOrFail((string) $purchaseOrderFulfillmentItemDetails['item_id']);

        $purchaseOrderFulfillmentItem->received_quantity = $purchaseOrderFulfillmentItemDetails['received_quantity'];
        $purchaseOrderFulfillmentItem->discrepancy_type = $purchaseOrderFulfillmentItemDetails['status'];
        $purchaseOrderFulfillmentItem->save();
    }

    public function updateDiscrepancyStatusById(
        int $purchaseOrderFulfillmentItemId,
        int $companyId,
        int $discrepancyStatus
    ): void {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrderFulfillmentItem = PurchaseOrderFulfillmentItem::query()
            ->select('id', 'purchase_order_fulfillment_id', 'received_quantity', 'discrepancy_type')
            ->whereHas('purchaseOrderFulfillment', function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->select('id', 'purchase_order_id')
                    ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->findOrFail($purchaseOrderFulfillmentItemId);

        $purchaseOrderFulfillmentItem->discrepancy_type = $discrepancyStatus;
        $purchaseOrderFulfillmentItem->save();
    }

    public function update(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        array $purchaseOrderFulfillmentItemData
    ): void {
        $purchaseOrderFulfillmentItem->update($purchaseOrderFulfillmentItemData);
    }

    public function getStatusById(int $purchaseOrderFulfillmentItemId, ?string $remarks): int
    {
        $remarks = $remarks ?: 'N/A';
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        $purchaseOrderFulfillmentItem = PurchaseOrderFulfillmentItem::select('id', 'purchase_order_fulfillment_id')
            ->with('purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns())
            ->where('id', $purchaseOrderFulfillmentItemId)
            ->firstOrFail();

        $purchaseOrderFulfillmentItem->remarks = $remarks;
        $purchaseOrderFulfillmentItem->save();

        /** @var PurchaseOrderFulfillment $purchaseOrderFulfillment */
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentItem->purchaseOrderFulfillment;

        return $purchaseOrderFulfillment->status;
    }

    public function deliveryNoteItemRemarks(User $user, ?string $remarks, int $purchaseOrderFulfillmentItemId): void
    {
        $status = $this->getStatusById($purchaseOrderFulfillmentItemId, $remarks);

        $purchaseOrderFulfillmentItemTransactionQueries = resolve(
            PurchaseOrderFulfillmentItemTransactionQueries::class
        );
        $purchaseOrderFulfillmentItemTransactionQueries->addNew(
            $purchaseOrderFulfillmentItemId,
            $remarks,
            $status,
            $user
        );
    }

    public function removeAdditionalItemAndRelations(int $purchaseOrderFulfillmentItemId): void
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        $purchaseOrderFulfillmentItem = PurchaseOrderFulfillmentItem::select('id', 'purchase_order_item_id')
            ->with('purchaseOrderItem')
            ->where('is_extra_item', true)
            ->where('id', $purchaseOrderFulfillmentItemId)
            ->firstOrFail();

        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        $purchaseOrderFulfillmentItem->units()->delete();
        $purchaseOrderFulfillmentItem->transactions()->delete();
        $purchaseOrderFulfillmentItem->itemBatches()->delete();
        $purchaseOrderFulfillmentItem->delete();
        if ($purchaseOrderItem instanceof PurchaseOrderItem) {
            $purchaseOrderItemQueries->delete($purchaseOrderItem);
        }
    }

    public function removeItemAndRelations(PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem): void
    {
        $purchaseOrderFulfillmentItem->units()->delete();
        $purchaseOrderFulfillmentItem->transactions()->delete();
        $purchaseOrderFulfillmentItem->itemBatches()->delete();
        $purchaseOrderFulfillmentItem->delete();
    }

    public function addReceivedQuantity(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        float $quantity,
    ): void {
        $purchaseOrderFulfillmentItem->received_quantity = $quantity;
        $purchaseOrderFulfillmentItem->save();
    }

    public function updateRemarks(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        string $remarks,
    ): void {
        $purchaseOrderFulfillmentItem->remarks = $remarks;
        $purchaseOrderFulfillmentItem->save();
    }

    public function addDiscrepancyProof(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        string $discrepancyProofUrl,
    ): void {
        $purchaseOrderFulfillmentItem->addMediaFromUrl($discrepancyProofUrl)->toMediaCollection('discrepancy_proof');
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrderFulfillmentProducts = PurchaseOrderFulfillmentItem::query()
            ->select('id', 'purchase_order_fulfillment_id', 'product_id')
            ->whereHas(
                'purchaseOrderFulfillment',
                function ($query) use ($companyId, $purchaseOrderQueries): void {
                    $query->select('id', 'purchase_order_id')
                        ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
                }
            )
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($purchaseOrderFulfillmentProducts as $purchaseOrderFulfillmentProduct) {
            $purchaseOrderFulfillmentProduct->product_id = $newProductId;
            $purchaseOrderFulfillmentProduct->save();
        }
    }

    public function printStickerTransferItems(array $transferItemIds, int $purchaseOrderFulfillmentId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);

        return PurchaseOrderFulfillmentItem::query()
            ->select(
                'id',
                'purchase_order_fulfillment_id',
                'purchase_order_item_id',
                'product_id',
                'transfer_quantity',
                'received_quantity',
                'package_type_id',
                'package_quantity',
                'package_total_quantity'
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'packageType:' . $packageTypeQueries->getBasicColumnNames(),
            )
            ->where('purchase_order_fulfillment_id', $purchaseOrderFulfillmentId)
            ->whereIntegerInRaw('id', $transferItemIds)
            ->get();
    }

    public function getWithPurchaseOrderFulfillmentAndPurchaseOrder(): Closure
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return fn ($query) => $query->select('id', 'purchase_order_fulfillment_id', 'discrepancy_type')
            ->with([
                'purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getIdAndOrderIdColumn(),
                'purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrderFulfillment.purchaseOrder.location:' . $locationQueries->getNameColumnName(),
                'purchaseOrderFulfillment.purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
            ]);
    }

    public function getByDateAndLocationWithProduct(array $filterData, int $companyId): Collection
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillmentItem::query()
                ->select(
                    'id',
                    'purchase_order_fulfillment_id',
                    'purchase_order_item_id',
                    'product_id',
                    'external_purchase_order_fulfillment_item_id',
                    'transfer_quantity',
                    'received_quantity',
                    'package_type_id',
                    'package_quantity',
                    'package_total_quantity'
                )
                ->with([
                    'purchaseOrderItem:' . $purchaseOrderItemQueries->getColumnForPrintInvoice(),
                    'product' => $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' => function ($query) use ($masterProductQueries): void {
                        $columns = explode(',', $masterProductQueries->getBasicColumnNames());
                        $query->select(...$columns)->where('is_non_inventory', false);
                    },
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                    'purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrderFulfillment.purchaseOrder.location',
                    'packageType:' . $packageTypeQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillment.purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillment.purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillment.purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
                ])
                ->whereHas('product.masterProduct', function ($query): void {
                    $query->where('is_non_inventory', false);
                })
                ->whereHas('purchaseOrderFulfillment', function ($query) use ($filterData): void {
                    $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]));
                    $query->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                })
                ->whereHas('purchaseOrderFulfillment', function ($query) use (
                    $companyId,
                    $filterData,
                    $purchaseOrderQueries,
                    $masterProductQueries
                ): void {
                    $query->select('id', 'purchase_order_id')
                        ->whereHas('purchaseOrder', function ($query) use (
                            $companyId,
                            $filterData,
                            $purchaseOrderQueries,
                            $masterProductQueries
                        ): void {
                            $query->where($purchaseOrderQueries->filterByCompany($companyId))
                                ->when((int) $filterData['external_location_id'], function ($query) use (
                                    $filterData
                                ): void {
                                    $query->where('external_location_id', (int) $filterData['external_location_id']);
                                })
                                ->when((int) $filterData['external_company_id'], function ($query) use (
                                    $filterData
                                ): void {
                                    $query->where('external_company_id', (int) $filterData['external_company_id']);
                                })
                                ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                                    $query->where('product_id', $filterData['product_id']);
                                })
                                ->when(null !== $filterData['article_number'], function ($query) use (
                                    $filterData,
                                    $masterProductQueries
                                ): void {
                                    $query->whereIn('product_id', function ($query) use (
                                        $filterData,
                                        $masterProductQueries
                                    ): void {
                                        $query->select('id', 'master_product_id')
                                            ->from('products')
                                            ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                            ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                                $query->where('article_number', $filterData['article_number']);
                                            });
                                    });
                                })
                                ->when(
                                    array_key_exists(
                                        'product_collection_id',
                                        $filterData
                                    ) && null !== $filterData['product_collection_id'],
                                    function ($query) use ($filterData): void {
                                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                                            $query->select('product_id')
                                                ->from('product_collection_products')
                                                ->where('product_collection_id', $filterData['product_collection_id']);
                                        });
                                    }
                                )
                                ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                                    $query->where('location_id', (int) $filterData['location_id']);
                                });
                        });
                })
                ->get();
        }

        return PurchaseOrderFulfillmentItem::query()
            ->select(
                'id',
                'purchase_order_fulfillment_id',
                'purchase_order_item_id',
                'product_id',
                'external_purchase_order_fulfillment_item_id',
                'transfer_quantity',
                'received_quantity',
                'package_type_id',
                'package_quantity',
                'package_total_quantity'
            )
            ->with([
                'purchaseOrderItem:' . $purchaseOrderItemQueries->getColumnForPrintInvoice(),
                'product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->isInventoryProduct();
                },
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrderFulfillment.purchaseOrder.location',
                'packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'purchaseOrderFulfillment.purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                'purchaseOrderFulfillment.purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'purchaseOrderFulfillment.purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
            ])
            ->whereHas('product', function ($query): void {
                $query->isInventoryProduct();
            })
            ->whereHas('purchaseOrderFulfillment', function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]));
                $query->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->whereHas('purchaseOrderFulfillment', function ($query) use (
                $companyId,
                $filterData,
                $purchaseOrderQueries
            ): void {
                $query->select('id', 'purchase_order_id')
                    ->whereHas('purchaseOrder', function ($query) use (
                        $companyId,
                        $filterData,
                        $purchaseOrderQueries
                    ): void {
                        $query->where($purchaseOrderQueries->filterByCompany($companyId))
                            ->when((int) $filterData['external_location_id'], function ($query) use (
                                $filterData
                            ): void {
                                $query->where('external_location_id', (int) $filterData['external_location_id']);
                            })
                            ->when((int) $filterData['external_company_id'], function ($query) use (
                                $filterData
                            ): void {
                                $query->where('external_company_id', (int) $filterData['external_company_id']);
                            })
                            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                                $query->where('product_id', $filterData['product_id']);
                            })
                            ->when(null !== $filterData['article_number'], function ($query) use (
                                $filterData
                            ): void {
                                $query->whereIn('product_id', function ($query) use ($filterData): void {
                                    $query->select('id')
                                        ->from('products')
                                        ->where('article_number', $filterData['article_number']);
                                });
                            })
                            ->when(
                                array_key_exists(
                                    'product_collection_id',
                                    $filterData
                                ) && null !== $filterData['product_collection_id'],
                                function ($query) use ($filterData): void {
                                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                                        $query->select('product_id')
                                            ->from('product_collection_products')
                                            ->where('product_collection_id', $filterData['product_collection_id']);
                                    });
                                }
                            )
                            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                                $query->where('location_id', (int) $filterData['location_id']);
                            });
                    });
            })
            ->get();
    }

    public function minusReceivedQuantity(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        int $partialReceiveId
    ): void {
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        foreach ($purchaseOrderFulfillment->items as $purchaseOrderFulfillmentItem) {
            $partialReceiveFulfillmentItem = $partiallyReceiveFulfillmentItemQueries->getPartiallyReceiveFulfillmentItemByPartialReceiveId(
                $partialReceiveId,
                $purchaseOrderFulfillmentItem->id
            );

            $newReceivedQuantity = max(
                0,
                $purchaseOrderFulfillmentItem->received_quantity - (int) $partialReceiveFulfillmentItem?->received_quantity
            );
            $purchaseOrderFulfillmentItem->received_quantity = $newReceivedQuantity;
            $purchaseOrderFulfillmentItem->save();
        }
    }
}
