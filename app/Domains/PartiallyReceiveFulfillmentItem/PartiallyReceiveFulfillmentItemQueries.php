<?php

declare(strict_types=1);

namespace App\Domains\PartiallyReceiveFulfillmentItem;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\PartiallyReceiveFulfillment\PartiallyReceiveFulfillmentQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\Size\SizeQueries;
use App\Models\PartiallyReceiveFulfillmentItem;
use App\Models\PurchaseOrderFulfillment;
use Closure;
use Illuminate\Support\Collection;

class PartiallyReceiveFulfillmentItemQueries
{
    public function addNew(array $partiallyReceiveFulfillmentItemData): void
    {
        PartiallyReceiveFulfillmentItem::create($partiallyReceiveFulfillmentItemData);
    }

    public function getPartiallyReceiveFulfillmentItems(int $partialReceiveId): Collection
    {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return PartiallyReceiveFulfillmentItem::query()
                ->select(
                    'id',
                    'partially_receive_fulfillment_id',
                    'purchase_order_fulfillment_item_id',
                    'received_quantity',
                )
                ->with([
                    'purchaseOrderFulfillmentItem:' . $purchaseOrderFulfillmentItemQueries->getBasicColumnForPartialReceive(),
                    'purchaseOrderFulfillmentItem.product:' . $productQueries->getBasicColumnNamesForPartialReceive(),
                    'purchaseOrderFulfillmentItem.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillmentItem.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillmentItem.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                ])
                ->where('partially_receive_fulfillment_id', $partialReceiveId)
                ->get();
        }

        return PartiallyReceiveFulfillmentItem::query()
            ->select(
                'id',
                'partially_receive_fulfillment_id',
                'purchase_order_fulfillment_item_id',
                'received_quantity',
            )
            ->with([
                'purchaseOrderFulfillmentItem:' . $purchaseOrderFulfillmentItemQueries->getBasicColumnForPartialReceive(),
                'purchaseOrderFulfillmentItem.product:' . $productQueries->getBasicColumnNamesForPartialReceive(),
                'purchaseOrderFulfillmentItem.product.color:' . $colorQueries->getBasicColumnNames(),
                'purchaseOrderFulfillmentItem.product.size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->where('partially_receive_fulfillment_id', $partialReceiveId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,partially_receive_fulfillment_id,purchase_order_fulfillment_item_id,received_quantity';
    }

    public function removePartial(int $partiallyReceiveFulfillmentId): void
    {
        PartiallyReceiveFulfillmentItem::where(
            'partially_receive_fulfillment_id',
            $partiallyReceiveFulfillmentId
        )->delete();
    }

    public function deleteReceivedQuantity(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        int $partialReceiveId
    ): void {
        foreach ($purchaseOrderFulfillment->items as $purchaseOrderFulfillmentItem) {
            PartiallyReceiveFulfillmentItem::where('partially_receive_fulfillment_id', $partialReceiveId)
                ->where('purchase_order_fulfillment_item_id', $purchaseOrderFulfillmentItem->id)
            ->delete();
        }
    }

    public function getPartiallyReceiveFulfillmentItemsWithTrashed(int $partialReceiveId): Collection
    {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return PartiallyReceiveFulfillmentItem::withTrashed()
                ->select(
                    'id',
                    'partially_receive_fulfillment_id',
                    'purchase_order_fulfillment_item_id',
                    'received_quantity',
                )
                ->with([
                    'purchaseOrderFulfillmentItem:' . $purchaseOrderFulfillmentItemQueries->getBasicColumnForPartialReceive(),
                    'purchaseOrderFulfillmentItem.product:' . $productQueries->getBasicColumnNamesForPartialReceive(),
                    'purchaseOrderFulfillmentItem.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillmentItem.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'purchaseOrderFulfillmentItem.product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                ])
                ->where('partially_receive_fulfillment_id', $partialReceiveId)
                ->get();
        }

        return PartiallyReceiveFulfillmentItem::withTrashed()
            ->select(
                'id',
                'partially_receive_fulfillment_id',
                'purchase_order_fulfillment_item_id',
                'received_quantity',
            )
            ->with([
                'purchaseOrderFulfillmentItem:' . $purchaseOrderFulfillmentItemQueries->getBasicColumnForPartialReceive(),
                'purchaseOrderFulfillmentItem.product:' . $productQueries->getBasicColumnNamesForPartialReceive(),
                'purchaseOrderFulfillmentItem.product.color:' . $colorQueries->getBasicColumnNames(),
                'purchaseOrderFulfillmentItem.product.size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->where('partially_receive_fulfillment_id', $partialReceiveId)
            ->get();
    }

    public function getPartiallyReceiveFulfillmentItemByPartialReceiveId(
        int $partialReceiveId,
        int $purchaseOrderFulfillmentItemId
    ): ?PartiallyReceiveFulfillmentItem {
        return PartiallyReceiveFulfillmentItem::query()
        ->select(
            'id',
            'partially_receive_fulfillment_id',
            'purchase_order_fulfillment_item_id',
            'received_quantity',
        )
        ->where('partially_receive_fulfillment_id', $partialReceiveId)
        ->where('purchase_order_fulfillment_item_id', $purchaseOrderFulfillmentItemId)
        ->first();
    }

    public function getPartiallyReceiveFulfillmentItemWithRelation(): Closure
    {
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);

        return fn ($query) => $query->select(
            'id',
            'partially_receive_fulfillment_id',
            'purchase_order_fulfillment_item_id',
            'received_quantity'
        )
            ->with([
                'partiallyReceiveFulfillment:' . $partiallyReceiveFulfillmentQueries->getBasicColumns(),
                'purchaseOrderFulfillmentItem:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'purchaseOrderFulfillmentItem.purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getIdAndOrderIdColumn(),
                'purchaseOrderFulfillmentItem.purchaseOrderFulfillment.purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrderFulfillmentItem.purchaseOrderFulfillment.purchaseOrder.location:' . $locationQueries->getNameColumnName(),
                'purchaseOrderFulfillmentItem.purchaseOrderFulfillment.purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
            ]);
    }
}
