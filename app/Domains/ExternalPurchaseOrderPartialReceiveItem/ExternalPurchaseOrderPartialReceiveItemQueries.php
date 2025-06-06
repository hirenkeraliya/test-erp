<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderPartialReceiveItem;

use App\Domains\Color\ColorQueries;
use App\Domains\ExternalPurchaseOrderItem\ExternalPurchaseOrderItemQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderPartialReceiveItemQueries
{
    public function addNew(array $externalPurchaseOrderPartialReceiveItemData): ExternalPurchaseOrderPartialReceiveItem
    {
        return ExternalPurchaseOrderPartialReceiveItem::create($externalPurchaseOrderPartialReceiveItemData);
    }

    public function update(
        ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem,
        array $externalPurchaseOrderPartialReceiveItemData
    ): void {
        $externalPurchaseOrderPartialReceiveItem->update($externalPurchaseOrderPartialReceiveItemData);
    }

    public function getByExternalPurchaseOrderPartialReceiveId(int $externalPurchaseOrderPartialReceiveId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $externalPurchaseOrderItemQueries = resolve(ExternalPurchaseOrderItemQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return ExternalPurchaseOrderPartialReceiveItem::query()
            ->select(
                'id',
                'external_purchase_order_partial_receive_id',
                'external_purchase_order_item_id',
                'quantity_received',
                'notes',
                'unit_of_measure_derivative_id'
            )
            ->with(
                'externalPurchaseOrderItem:' . $externalPurchaseOrderItemQueries->getBasicColumnNames(),
                'externalPurchaseOrderItem.product:' . $productQueries->getBasicColumnNames(),
                'externalPurchaseOrderItem.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'externalPurchaseOrderItem.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
            )
            ->where('external_purchase_order_partial_receive_id', $externalPurchaseOrderPartialReceiveId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,external_purchase_order_partial_receive_id,external_purchase_order_item_id,quantity_received,notes,unit_of_measure_derivative_id';
    }

    public function removeItemAndRelations(
        ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem
    ): void {
        $externalPurchaseOrderPartialReceiveItem->itemBatches()->delete();
        $externalPurchaseOrderPartialReceiveItem->delete();
    }
}
