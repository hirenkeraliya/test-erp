<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem;

use App\Domains\Color\ColorQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\ExternalPurchaseOrderItem;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderItemQueries
{
    public function addNew(array $externalPurchaseOrderItem): ExternalPurchaseOrderItem
    {
        return ExternalPurchaseOrderItem::create($externalPurchaseOrderItem);
    }

    public function update(
        ExternalPurchaseOrderItem $externalPurchaseOrderItem,
        array $purchaseOrderItemData
    ): void {
        $externalPurchaseOrderItem->update($purchaseOrderItemData);
    }

    public function removeItemAndRelations(ExternalPurchaseOrderItem $externalPurchaseOrderItem): void
    {
        $externalPurchaseOrderItem->delete();
    }

    public function getByExternalPurchaseOrderId(int $externalPurchaseOrderId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        return ExternalPurchaseOrderItem::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'purchase_plan_item_id',
                'product_id',
                'quantity',
                'received_quantity',
                'cost_price',
                'charge_per_unit',
                'total_price',
                'remarks',
                'unit_of_measure_derivative_id'
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
            )
            ->where('external_purchase_order_id', $externalPurchaseOrderId)
            ->get();
    }

    public function getByIds(array $externalPurchaseOrderItemIds): Collection
    {
        return ExternalPurchaseOrderItem::query()
            ->select('id', 'external_purchase_order_id', 'product_id', 'quantity', 'received_quantity', 'remarks')
            ->whereInCaseSensitive('id', $externalPurchaseOrderItemIds)
            ->get();
    }

    public function updateReceivedQuantity(
        ExternalPurchaseOrderItem $externalPurchaseOrderItem,
        float $receivedQuantity
    ): void {
        $externalPurchaseOrderItem->received_quantity += $receivedQuantity;
        $externalPurchaseOrderItem->save();
    }

    public function decreaseItemQuantity(
        ExternalPurchaseOrderItem $externalPurchaseOrderItem,
        float $receivedQuantity
    ): void {
        $externalPurchaseOrderItem->received_quantity -= $receivedQuantity;
        $externalPurchaseOrderItem->save();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,external_purchase_order_id,purchase_plan_item_id,product_id,quantity,received_quantity,cost_price,charge_per_unit,total_price,remarks,unit_of_measure_derivative_id';
    }
}
