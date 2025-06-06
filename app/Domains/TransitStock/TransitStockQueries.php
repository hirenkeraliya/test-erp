<?php

declare(strict_types=1);

namespace App\Domains\TransitStock;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\StockTransferItem;
use App\Models\TransitStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransitStockQueries
{
    public function addNew(array $records): void
    {
        TransitStock::create($records);
    }

    public function deleteAffectedBy(int $moduleId, string $module): void
    {
        $transitStocks = TransitStock::query()
            ->where('affected_by_id', $moduleId)
            ->where('affected_by_type', $module)
            ->get();

        foreach ($transitStocks as $transitStock) {
            $transitStock->delete();
        }
    }

    public function getQuantityColumn(): string
    {
        return 'id,inventory_id,quantity';
    }

    public function getPaginatedTransitInventoryForLocation(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getTransitInventoryLocationQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getTransitInventoryLocationForExport(array $filterData, int $companyId): Collection
    {
        return $this->getTransitInventoryLocationQuery($filterData, $companyId)->get();
    }

    public function getConsolidatedData(array $filterData, int $companyId): ?TransitStock
    {
        $productQueries = resolve(ProductQueries::class);

        return TransitStock::query()
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
                            $productQueries->filterForTheTransitStock($filterData, $companyId)
                        );
                    });
            })
            ->first();
    }

    private function getTransitInventoryLocationQuery(array $filterData, int $companyId): Builder
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'inventory:' . $inventoryQueries->getBasicColumnNames(),
            'inventory.product:' . $productQueries->getColumnsForTransitInventoryReports(),
            'affectedBy' => function (MorphTo $morphTo) use (
                $stockTransferQueries,
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
                ]);
            },
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'inventory.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'inventory.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge(
                $relations,
                [
                    'inventory.product.color:' . $colorQueries->getBasicColumnNames(),
                    'inventory.product.size:' . $sizeQueries->getBasicColumnNames(),
                ]
            );
        }

        return TransitStock::query()
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
                            $productQueries->filterForTheTransitStock($filterData, $companyId)
                        );
                    });
            })
            ->orderBy('id', 'desc');
    }

    public function updateInventoryIdDuringProductMerge(TransitStock $transitStock, int $newInventoryId): void
    {
        $transitStock->notes .= ' | Product Merge. old Inventory Id: ' . $transitStock->inventory_id;
        $transitStock->inventory_id = $newInventoryId;
        $transitStock->save();
    }

    public function partialDeleteAffectedBy(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        string $module,
        float $receivedQuantity
    ): void {
        TransitStock::query()
            ->where('affected_by_id', $purchaseOrderFulfillmentItem->id)
            ->where('affected_by_type', $module)
            ->decrement('quantity', $receivedQuantity);

        TransitStock::query()
            ->where('affected_by_id', $purchaseOrderFulfillmentItem->id)
            ->where('affected_by_type', $module)
            ->where('quantity', '<=', 0)
            ->delete();
    }
}
