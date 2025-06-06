<?php

declare(strict_types=1);

namespace App\Domains\ReservedStock;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Model;
use App\Models\OrderItem;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\ReservedStock;
use App\Models\SaleItem;
use App\Models\StockTransferItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReservedStockQueries
{
    public function addNew(
        int $inventoryId,
        int $inventoryUnitId,
        float $quantity,
        Model $affectedBy,
        ?string $notes = null,
    ): void {
        ReservedStock::create([
            'inventory_id' => $inventoryId,
            'inventory_unit_id' => $inventoryUnitId,
            'affected_by_id' => $affectedBy->id,
            'affected_by_type' => ModelMapping::getCaseName($affectedBy::class),
            'quantity' => $quantity,
            'notes' => $notes,
        ]);
    }

    public function getByAffectedBy(Model $affectedBy): Collection
    {
        $inventoryUnitQueries = new InventoryUnitQueries();
        $inventoryQueries = new InventoryQueries();

        return ReservedStock::select(
            'id',
            'inventory_id',
            'inventory_unit_id',
            'affected_by_id',
            'affected_by_type',
            'quantity',
            'notes'
        )
            ->with([
                'inventory:' . $inventoryQueries->getColumnForReservedStock(),
                'inventoryUnit:' . $inventoryUnitQueries->getBasicColumnNames(),
            ])
            ->where('affected_by_id', $affectedBy->id)
            ->where('affected_by_type', ModelMapping::getCaseName($affectedBy::class))
            ->lockForUpdate()
            ->get();
    }

    public function getByAffectedByIds(array $affectedByIds, string $affectedByType): Collection
    {
        $inventoryUnitQueries = new InventoryUnitQueries();
        $inventoryQueries = new InventoryQueries();

        return ReservedStock::select(
            'id',
            'inventory_id',
            'inventory_unit_id',
            'affected_by_id',
            'affected_by_type',
            'quantity',
            'notes'
        )
            ->with([
                'inventory:' . $inventoryQueries->getColumnForReservedStock(),
                'inventoryUnit:' . $inventoryUnitQueries->getBasicColumnNames(),
            ])
            ->whereIn('affected_by_id', $affectedByIds)
            ->where('affected_by_type', ModelMapping::getCaseName($affectedByType))
            ->lockForUpdate()
            ->get();
    }

    public function updateAffectedByType(ReservedStock $reservedStock, int $salesOrderItemId): void
    {
        $reservedStock->affected_by_id = $salesOrderItemId;
        $reservedStock->save();
    }

    public function delete(ReservedStock $reservedStock): void
    {
        $reservedStock->delete();
    }

    public function decrementQuantity(ReservedStock $reservedStock, float $newQuantity): void
    {
        $reservedStock->decrement('quantity', $newQuantity);
        $reservedStock->save();
    }

    public function decrementQuantityWithLatestReservedStock(
        ReservedStock $reservedStock,
        float $newQuantity
    ): ReservedStock {
        $reservedStock->decrement('quantity', $newQuantity);
        $reservedStock->save();

        return $reservedStock->refresh();
    }

    public function incrementQuantity(ReservedStock $reservedStock, float $newQuantity): void
    {
        $reservedStock->increment('quantity', $newQuantity);
        $reservedStock->save();
    }

    public function updateInventoryId(ReservedStock $reservedStock, int $newInventoryId): void
    {
        $reservedStock->inventory_id = $newInventoryId;
        $reservedStock->save();
    }

    public function getPaginatedReservedInventoryForLocation(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getReservedInventoryLocationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getConsolidatedData(array $filterData, int $companyId): ?ReservedStock
    {
        $productQueries = resolve(ProductQueries::class);

        return ReservedStock::query()
            ->select(DB::raw('SUM(quantity) as quantity'))
            ->whereHas('inventory', function ($query) use ($filterData, $companyId, $productQueries): void {
                $query->select('id', 'location_id', 'product_id')
                    ->where('location_id', $filterData['location_id'])
                    ->when($filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            $query->select('product_id')
                                ->from('product_collection_products')
                                ->where('product_collection_id', (int) $filterData['product_collection_id']);
                        });
                    })
                    ->when($filterData['search_text'], function ($query) use (
                        $filterData,
                        $productQueries,
                        $companyId
                    ): void {
                        $query->whereHas(
                            'product',
                            $productQueries->filterForTheReservedStock($filterData, $companyId)
                        );
                    });
            })
            ->first();
    }

    public function getReservedInventoryLocationForExport(array $filterData, int $companyId): Collection
    {
        return $this->getReservedInventoryLocationQuery($filterData, $companyId)->get();
    }

    private function getReservedInventoryLocationQuery(array $filterData, int $companyId): Builder
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'inventory:' . $inventoryQueries->getBasicColumnNames(),
            'inventory.product:' . $productQueries->getColumnsForReservedInventoryReports(),
            'affectedBy' => function (MorphTo $morphTo) use (
                $stockTransferQueries,
                $saleQueries,
                $orderQueries,
                $purchaseOrderFulfillmentItemQueries
            ): void {
                $morphTo->constrain([
                    StockTransferItem::class => function ($query) use ($stockTransferQueries): void {
                        $query->select('id', 'stock_transfer_id')
                            ->with([
                                'stockTransfer:' . $stockTransferQueries->getStockTransferColumns(),
                                'stockTransfer.sourceLocation:' . $stockTransferQueries->getLocationColumnName(),
                                'stockTransfer.destinationLocation:' . $stockTransferQueries->getLocationColumnName(),
                            ]);
                    },
                    PurchaseOrderFulfillmentItem::class => $purchaseOrderFulfillmentItemQueries->getWithPurchaseOrderFulfillmentAndPurchaseOrder(),
                    SaleItem::class => function ($query) use ($saleQueries): void {
                        $query->select('id', 'sale_id')
                            ->with(['sale:' . $saleQueries->getOfflineSaleIdWithStatus()]);
                    },
                    OrderItem::class => function ($query) use ($orderQueries): void {
                        $query->select('id', 'order_id')
                            ->with(['order:' . implode(',', $orderQueries->getBasicColumns())]);
                    },
                ]);
            },
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'inventory.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'inventory.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'inventory.product.color:' . $colorQueries->getBasicColumnNames(),
                'inventory.product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return ReservedStock::query()
            ->select('id', 'inventory_id', 'affected_by_id', 'affected_by_type', 'quantity')
            ->with($relations)
            ->whereHas('inventory', function ($query) use ($filterData, $companyId, $productQueries): void {
                $query->select('id', 'location_id', 'product_id')
                    ->where('location_id', $filterData['location_id'])
                    ->when($filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            $query->select('product_id')
                                ->from('product_collection_products')
                                ->where('product_collection_id', (int) $filterData['product_collection_id']);
                        });
                    })
                    ->when($filterData['search_text'], function ($query) use (
                        $filterData,
                        $productQueries,
                        $companyId
                    ): void {
                        $query->whereHas(
                            'product',
                            $productQueries->filterForTheReservedStock($filterData, $companyId)
                        );
                    });
            })
            ->orderBy('id', 'desc');
    }
}
