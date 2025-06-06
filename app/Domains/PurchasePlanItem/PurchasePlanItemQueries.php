<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlanItem;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\PurchasePlanItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchasePlanItemQueries
{
    public function addNew(array $purchasePlanItemData): PurchasePlanItem
    {
        return PurchasePlanItem::create($purchasePlanItemData);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,purchase_plan_id,product_id,quantity,transferred_quantity,cost_price,total_price,remarks,is_product_purchase_cost,unit_of_measure_derivative_id';
    }

    public function getColumnIdAndProductId(): string
    {
        return 'id,purchase_plan_id,product_id';
    }

    public function getColumnForPrintInvoice(): string
    {
        return 'id,purchase_plan_id,product_id,cost_price';
    }

    public function getByPurchasePlanId(int $purchasePlanId): Collection
    {
        return $this->commonGetByPurchasePlanId($purchasePlanId)->get();
    }

    public function getById(int $purchasePlanItemId): PurchasePlanItem
    {
        return PurchasePlanItem::query()
            ->select(
                'id',
                'purchase_plan_id',
                'product_id',
                'quantity',
                'transferred_quantity',
                'cost_price',
                'total_price',
                'is_product_purchase_cost',
                'remarks',
            )
            ->findOrFail($purchasePlanItemId);
    }

    private function commonGetByPurchasePlanId(int $purchasePlanId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);

        if (config('app.product_variant')) {
            return PurchasePlanItem::query()
                ->select(
                    'id',
                    'purchase_plan_id',
                    'product_id',
                    'quantity',
                    'transferred_quantity',
                    'cost_price',
                    'total_price',
                    'is_product_purchase_cost',
                    'unit_of_measure_derivative_id',
                    'remarks',
                )
                ->with(
                    'product:' . $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                )
                ->where('purchase_plan_id', $purchasePlanId);
        }

        return PurchasePlanItem::query()
            ->select(
                'id',
                'purchase_plan_id',
                'product_id',
                'quantity',
                'transferred_quantity',
                'cost_price',
                'total_price',
                'unit_of_measure_derivative_id',
                'is_product_purchase_cost',
                'remarks',
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
            )
            ->where('purchase_plan_id', $purchasePlanId);
    }

    public function getByIds(array $purchaseOrderItemIds): Collection
    {
        return PurchasePlanItem::query()
            ->select('id', 'purchase_plan_id', 'product_id', 'quantity', 'transferred_quantity', 'remarks')
            ->whereInCaseSensitive('id', $purchaseOrderItemIds)
            ->get();
    }

    public function updateTransferredQuantity(
        PurchasePlanItem $purchasePlanItem,
        float $transferredQuantity
    ): void {
        $purchasePlanItem->transferred_quantity += $transferredQuantity;
        $purchasePlanItem->save();
    }

    public function decreaseTransferredQuantity(
        PurchasePlanItem $purchasePlanItem,
        float $transferredQuantity
    ): void {
        $purchasePlanItem->transferred_quantity -= $transferredQuantity;
        $purchasePlanItem->save();
    }
}
