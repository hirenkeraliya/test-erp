<?php

declare(strict_types=1);

namespace App\Domains\Batch;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Models\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BatchQueries
{
    public function addNewAndGetId(array $batchDetails, int $companyId, int $productId): int
    {
        return Batch::firstOrCreate([
            'company_id' => $companyId,
            'product_id' => $productId,
            'number' => $batchDetails['batch_number'],
        ], [
            'expiry_date' => $batchDetails['batch_expiry_date'],
            'external_id' => $batchDetails['batch_external_id'],
            'notes' => $batchDetails['batch_notes'],
        ])->id;
    }

    public function addNew(int $companyId, int $productId, string $batchNumber, string $expiryDate): Batch
    {
        return Batch::firstOrCreate([
            'company_id' => $companyId,
            'product_id' => $productId,
            'number' => $batchNumber,
        ], [
            'expiry_date' => $expiryDate,
        ]);
    }

    public function getByNumbers(array $batchNumbers, int $companyId): Collection
    {
        return Batch::select('id', 'product_id', 'number', 'expiry_date')
            ->where('company_id', $companyId)
            ->whereInCaseSensitive('number', $batchNumbers)
            ->get();
    }

    public function getByNumbersWithProductUpc(array $batchNumbers, string $productUpc): Collection
    {
        return Batch::query()
            ->select('id', 'product_id', 'number', 'expiry_date')
            ->whereHas('product', function ($query) use ($productUpc): void {
                $query->where('upc', $productUpc);
            })
            ->whereInCaseSensitive('number', $batchNumbers)
            ->get();
    }

    public function getByNumber(string $batchNumber, int $companyId): ?Batch
    {
        return Batch::select('id', 'product_id', 'number', 'expiry_date')
            ->where('company_id', $companyId)
            ->where('number', $batchNumber)
            ->first();
    }

    public function getExpiryDateSubOrderQuery(string $whereColumn): Builder
    {
        return Batch::select('expiry_date')->whereColumn($whereColumn, 'id');
    }

    public function getIdNumberAndExpiryDateColumnNames(): string
    {
        return 'id,expiry_date,number';
    }

    public function getByProductIds(array $productIds, int $companyId): Collection
    {
        return Batch::query()
            ->select('id', 'product_id', 'number', 'expiry_date', 'external_id', 'notes')
            ->whereIntegerInRaw('product_id', $productIds)
            ->where('company_id', $companyId)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,number,expiry_date';
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $batches = Batch::query()
            ->select('id', 'company_id', 'product_id')
            ->where('company_id', $companyId)
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($batches as $batch) {
            $batch->product_id = $newProductId;
            $batch->save();
        }
    }

    public function batchExpiryReportList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->commonBatchExpiryReportListQuery($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function batchExpiryReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->commonBatchExpiryReportListQuery($filterData, $companyId)
            ->get();
    }

    private function commonBatchExpiryReportListQuery(array $filterData, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'product:' . $productQueries->getColumnsForBatchExpiryReports(),
            'inventoryUnit:' . $inventoryUnitQueries->getColumnForBatchExpiryReport(),
            'inventoryUnit.inventory:' . $inventoryQueries->getColumnForBatchExpiryReport(),
            'inventoryUnit.inventory.location:' . $this->getMorphLocationBasicColumns(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        }

        return Batch::select('id', 'product_id', 'number', 'expiry_date')
            ->with($relations)
            ->whereHas('product', function ($query) use ($companyId): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($query) use ($companyId): void {
                        $query->select('id')
                            ->where('company_id', $companyId)
                            ->where('has_batch', true);
                    });
                } else {
                    $query->select('id')
                        ->where('company_id', $companyId)
                        ->where('has_batch', true);
                }
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->whereAny(['upc', 'name'], 'LIKE', '%' . $filterData['search_text'] . '%');
                    })
                        ->orWhere('number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->whereHas('inventoryUnit', function ($query) use ($filterData): void {
                    $query->select('id', 'inventory_id')
                        ->whereHas('inventory', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->where('location_id', $filterData['location_id']);
                        });
                });
            })
            ->when($filterData['category_id'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $categoryQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $categoryQueries): void {
                            $query->whereHas('categories', function ($query) use ($filterData, $categoryQueries): void {
                                $query->select('id')
                                    ->where($categoryQueries->filterById((int) $filterData['category_id']));
                            });
                        });
                    } else {
                        $query->whereHas('categories', function ($query) use ($filterData, $categoryQueries): void {
                            $query->select('id')
                                ->where($categoryQueries->filterById((int) $filterData['category_id']));
                        });
                    }
                });
            })
            ->when($filterData['brand_id'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->where('brand_id', $filterData['brand_id']);
                        });
                    } else {
                        $query->where('brand_id', $filterData['brand_id']);
                    }
                });
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('tag_id', $filterData['tag_ids']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('expiry_date', '<=', $filterData['date_range']);
            })
            ->orderBy('expiry_date', 'asc');
    }

    public function getMorphLocationBasicColumns(): string
    {
        return 'id,name,type_id';
    }
}
