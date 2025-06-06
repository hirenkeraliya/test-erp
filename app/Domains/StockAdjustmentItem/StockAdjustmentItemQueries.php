<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustmentItem;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\StockAdjustment\Enums\StockAdjustmentFilterType;
use App\Domains\StockAdjustment\StockAdjustmentQueries;
use App\Models\StockAdjustmentItem;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StockAdjustmentItemQueries
{
    public function addNew(
        float $quantity,
        int $stockAdjustmentId,
        int $productId,
        int $locationId,
        ?int $derivativeId,
        ?float $inputQuantity,
        ?float $currentDerivateRatio,
        ?int $purchaseAmountId,
        ?int $batchId,
    ): StockAdjustmentItem {
        return StockAdjustmentItem::create([
            'stock_adjustment_id' => $stockAdjustmentId,
            'product_id' => $productId,
            'location_id' => $locationId,
            'unit_of_measure_derivative_id' => $derivativeId,
            'input_quantity' => $inputQuantity,
            'derivative_ratio' => $currentDerivateRatio,
            'quantity' => $quantity,
            'purchase_amount_id' => $purchaseAmountId,
            'batch_id' => $batchId,
        ]);
    }

    public function getItemsByStockAdjustmentId(int $stockAdjustmentId, int $companyId): Collection
    {
        return $this->getItemsByStockAdjustmentIdQuery($stockAdjustmentId, $companyId)->get();
    }

    public function getLocationBasicColumn(): string
    {
        return 'id,name,code,type_id';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,stock_adjustment_id,product_id,location_id,quantity';
    }

    public function getItemsByStockAdjustmentIdForWarehouseManager(
        int $stockAdjustmentId,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->getItemsByStockAdjustmentIdQuery($stockAdjustmentId, $companyId)
            ->where('location_id', $locationId)
            ->get();
    }

    public function getItemsByStockAdjustmentIdForStoreManager(
        int $stockAdjustmentId,
        int $companyId,
        int $locationId
    ): Collection {
        return $this->getItemsByStockAdjustmentIdQuery($stockAdjustmentId, $companyId)
            ->where('location_id', $locationId)
            ->get();
    }

    public function getItemsByStockAdjustmentIdQuery(int $stockAdjustmentId, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $stockAdjustmentQueries = new StockAdjustmentQueries();

        return StockAdjustmentItem::query()
            ->select('id', 'product_id', 'location_id', 'quantity')
            ->with([
                'product:' . $productQueries->getBasicColumnNames(),
                'location:' . $this->getLocationBasicColumn(),
            ])
            ->where('stock_adjustment_id', $stockAdjustmentId)
            ->whereHas('stockAdjustment', $stockAdjustmentQueries->filterByCompany($companyId));
    }

    public function getLocationItems(int $locationId): Closure
    {
        return fn ($query) => $query->where('location_id', $locationId);
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $stockAdjustmentQueries = resolve(StockAdjustmentQueries::class);

        $stockAdjustmentItems = StockAdjustmentItem::query()
            ->select('id', 'stock_adjustment_id', 'product_id')
            ->whereHas('stockAdjustment', $stockAdjustmentQueries->filterByCompany($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($stockAdjustmentItems as $stockAdjustmentItem) {
            $stockAdjustmentItem->product_id = $newProductId;
            $stockAdjustmentItem->save();
        }
    }

    public function getStockAdjustmentForStockCardPrint(): Closure
    {
        $stockAdjustmentQueries = resolve(StockAdjustmentQueries::class);

        return fn ($query) => $query->select('id', 'stock_adjustment_id')
            ->with(['stockAdjustment:' . $stockAdjustmentQueries->getColumnsForStockCardPrint()]);
    }

    public function getStockAdjustmentWithRelation(): Closure
    {
        $stockAdjustmentQueries = resolve(StockAdjustmentQueries::class);

        return fn ($query) => $query->select('id', 'stock_adjustment_id')
            ->with(['stockAdjustment:' . $stockAdjustmentQueries->getColumns()]);
    }

    public function getItemsByDateAndLocations(array $filterData, int $companyId): Collection
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $stockAdjustmentQueries = resolve(StockAdjustmentQueries::class);
        $columns = explode(',', $stockAdjustmentQueries->getColumnsForPrint());

        return StockAdjustmentItem::query()
            ->select('id', 'stock_adjustment_id', 'product_id', 'location_id', 'quantity')
            ->withWhereHas('stockAdjustment', function ($query) use (
                $companyId,
                $filterData,
                $columns,
                $employeeQueries
            ): void {
                $query->select(...$columns)
                    ->with('employee:' . $employeeQueries->getNameAndStaffIdColumns())
                    ->where('company_id', $companyId)
                    ->where(function ($query) use ($filterData): void {
                        $query->when(
                            null !== $filterData['stock_adjustment_type'],
                            function ($query) use ($filterData): void {
                                $query->where('type_id', $filterData['stock_adjustment_type']);
                            }
                        );
                    })
                    ->where(function ($query) use ($filterData): void {
                        $query->where(function ($query) use ($filterData): void {
                            $query->where('adjustment_date', '>=', $filterData['date_range'][0])
                                ->where('adjustment_date', '<=', $filterData['date_range'][1]);
                        })
                        ->orWhere(function ($query) use ($filterData): void {
                            $query->where('created_at', '>=', $filterData['date_range'][0])
                                ->where('created_at', '<=', $filterData['date_range'][1]);
                        });
                    });
            })
            ->withWhereHas('product', function ($query) use ($filterData, $companyId): void {
                if (config('app.product_variant')) {
                    $query->select('products.id', 'products.name', 'products.upc', 'master_products.article_number')
                        ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')->where(
                            'master_products.is_non_inventory',
                            false
                        );
                } else {
                    $query->select('id', 'name', 'upc', 'article_number')->where('is_non_inventory', false);
                }

                $query->when(null !== $filterData['filter_by'], function ($query) use (
                    $filterData,
                    $companyId
                ): void {
                    $query->when(
                        (int) $filterData['filter_by'] === StockAdjustmentFilterType::BY_PRODUCT->value,
                        function ($query) use ($filterData, $companyId): void {
                            $query->where('products.id', (int) $filterData['product_id'])
                                ->where('products.company_id', $companyId);
                        }
                    )
                    ->when(
                        (int) $filterData['filter_by'] === StockAdjustmentFilterType::BY_MASTER_PRODUCT->value,
                        function ($query) use ($filterData, $companyId): void {
                            if (config('app.product_variant')) {
                                $query->whereHas('masterProduct', function ($query) use (
                                    $filterData,
                                    $companyId
                                ): void {
                                    $query->where('article_number', $filterData['article_number'])
                                        ->where('company_id', $companyId);
                                });
                            } else {
                                $query->where('article_number', $filterData['article_number'])
                                    ->where('company_id', $companyId);
                            }
                        }
                    );
                });
            })
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        $query->select('product_id')
                            ->from('product_collection_products')
                            ->where('product_collection_id', (int) $filterData['product_collection_id']);
                    });
                }
            )
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->get();
    }
}
