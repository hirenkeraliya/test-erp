<?php

declare(strict_types=1);

namespace App\Domains\StockTakeProduct;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StockTake\StockTakeQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Models\StockTake;
use App\Models\StockTakeProduct;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class StockTakeProductQueries
{
    public function addNewWithoutActualStock(int $productId, StockTake $stockTake): void
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $records = [
            'stock_take_id' => $stockTake->id,
            'product_id' => $productId,
            'actual_stock' => 0,
            'submitted_stock' => 0,
            'created_at' => $currentTime,
            'updated_at' => $currentTime,
        ];

        StockTakeProduct::insert($records);
    }

    /**
     * @return mixed[]
     */
    public function getProductIdsByStockTakeId(int $stockTakeId): array
    {
        $stockTakeProducts = StockTakeProduct::select('id', 'product_id')
            ->where('stock_take_id', $stockTakeId)
            ->get();

        return $stockTakeProducts->pluck('product_id')->toArray();
    }

    public function updateProductActualStock(int $productId, float $actualStock, int $stockTakeId): void
    {
        /** @var StockTakeProduct $stockTakeProduct */
        $stockTakeProduct = StockTakeProduct::where('stock_take_id', $stockTakeId)
            ->where('product_id', $productId)
            ->first();

        $stockTakeProduct->update([
            'actual_stock' => $actualStock,
        ]);

        $stockTakeQueries = resolve(StockTakeQueries::class);
        $stockTakeQueries->setUpdatedAt($stockTakeId);
    }

    public function getLists(array $filterData, int $stockTakeId, int $locationId, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTakeQueries = resolve(StockTakeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        return StockTakeProduct::select('id', 'product_id', 'submitted_stock')
            ->with(
                'product:' . $productQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            )
            ->where('stock_take_id', $stockTakeId)
            ->whereHas('stockTake', $stockTakeQueries->filterByLocationIdAndCompanyId($locationId, $companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', $productQueries->searchByCompoundProductNameUpcAndArticleNumber(
                    $filterData['search_text']
                ));
            })
            ->orderBy('submitted_stock', 'ASC')
            ->get();
    }

    public function getProductsOfSubmittedStockTake(
        int $stockTakeId,
        int $locationId,
        int $companyId
    ): EloquentCollection {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTakeQueries = resolve(StockTakeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        return StockTakeProduct::select('id', 'product_id', 'submitted_stock')
            ->with(
                'product:' . $productQueries->getBasicColumnNamesForStockTakeExport(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            )
            ->where('stock_take_id', $stockTakeId)
            ->whereHas('stockTake', $stockTakeQueries->fetchSubmittedOnly($locationId, $companyId))
            ->get();
    }

    public function downloadStockTakeProducts(
        int $stockTakeId,
        int $locationId,
        array $filterData,
        int $companyId,
    ): EloquentCollection {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTakeQueries = resolve(StockTakeQueries::class);
        $brandQueries = new BrandQueries();
        $departmentQueries = new DepartmentQueries();
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        return StockTakeProduct::select('id', 'product_id', 'submitted_stock')
            ->with(
                'product:' . $productQueries->getBasicColumnNamesForStockTakeExport(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.department:' . $departmentQueries->getBasicColumnNames(),
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            )
            ->where('stock_take_id', $stockTakeId)
            ->whereHas('stockTake', $stockTakeQueries->filterByLocationIdAndCompanyId($locationId, $companyId))
            ->whereHas('product', function ($query) use ($filterData): void {
                $query->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
                })
                ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
                })
                ->when($filterData['color_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('color_id', (array) $filterData['color_ids']);
                })
                ->when($filterData['size_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('size_id', (array) $filterData['size_ids']);
                });
            })
            ->get()->sortBy('product.name');
    }

    public function getSubmittedStockTakeProductsByStockTakeId(int $stockTakeId, int $companyId): EloquentCollection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTakeQueries = resolve(StockTakeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = ['product:' . $productQueries->getBasicColumnNamesForStockTakeExport()];

        if (config('app.product_variant')) {
            $relations = array_merge(
                $relations,
                [
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                ]
            );
        } else {
            $relations = array_merge(
                $relations,
                [
                    'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                    'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                    'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                ]
            );
        }

        return StockTakeProduct::select('id', 'product_id', 'actual_stock', 'submitted_stock')
            ->with($relations)
            ->where('stock_take_id', $stockTakeId)
            ->whereHas('stockTake', $stockTakeQueries->filterSubmittedById($stockTakeId))
            ->whereHas('product', $productQueries->filterByCompany($companyId))
            ->get();
    }

    public function updateSubmittedStock(
        array $validatedData,
        int $stockTakeId,
        int $locationId,
        int $companyId,
    ): void {
        $stockTakeQueries = resolve(StockTakeQueries::class);

        $stockTakeProduct = StockTakeProduct::query()
            ->where('stock_take_id', $stockTakeId)
            ->where('product_id', $validatedData['product_id'])
            ->whereHas('stockTake', $stockTakeQueries->filterByLocationIdAndCompanyId($locationId, $companyId))
            ->findOrFail((int) $validatedData['stock_take_product_id']);

        $stockTakeProduct->submitted_stock = $validatedData['submitted_stock'];
        $stockTakeProduct->save();

        $stockTakeQueries = resolve(StockTakeQueries::class);
        $stockTakeQueries->setUpdatedAt($stockTakeId);
    }

    public function updateSubmittedStockByStockId(
        array $validatedData,
        int $stockTakeId,
        int $locationId,
        int $companyId
    ): void {
        $stockTakeQueries = resolve(StockTakeQueries::class);

        $stockTakeProducts = StockTakeProduct::query()
            ->select('*')
            ->where('stock_take_id', $stockTakeId)
            ->where('product_id', $validatedData['product_id'])
            ->whereHas('stockTake', $stockTakeQueries->filterByLocationIdAndCompanyId($locationId, $companyId))
            ->get();
        foreach ($stockTakeProducts as $stockTakeProduct) {
            $stockTakeProduct->submitted_stock = $validatedData['submitted_stock'];
            $stockTakeProduct->save();
        }

        $stockTakeQueries = resolve(StockTakeQueries::class);
        $stockTakeQueries->setUpdatedAt($stockTakeId);
    }

    public function bulkUpdateSubmitStock(array $record): void
    {
        StockTakeProduct::query()
            ->updateOrCreate([
                'stock_take_id' => $record['stock_take_id'],
                'product_id' => $record['product_id'],
            ], [
                'submitted_stock' => $record['submitted_stock'],
            ]);

        $stockTakeQueries = resolve(StockTakeQueries::class);
        $stockTakeQueries->setUpdatedAt((int) $record['stock_take_id']);
    }

    public function getPendingStockProductsSubmissionCount(
        int $stockTakeId,
        int $locationId,
        int $companyId
    ): int {
        $stockTakeQueries = resolve(StockTakeQueries::class);

        return StockTakeProduct::select('id', 'product_id', 'submitted_stock')
            ->where('stock_take_id', $stockTakeId)
            ->where('submitted_stock', '<=', '0')
            ->whereHas('stockTake', $stockTakeQueries->filterByLocationIdAndCompanyId($locationId, $companyId))
            ->count();
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $stockTakeQueries = resolve(StockTakeQueries::class);

        $stockTakeProducts = StockTakeProduct::query()
            ->select('id', 'stock_take_id', 'product_id')
            ->where('product_id', $oldProductId)
            ->whereHas('product', $productQueries->filterByCompany($companyId))
            ->get();

        foreach ($stockTakeProducts as $stockTakeProduct) {
            $stockTakeProduct->product_id = $newProductId;
            $stockTakeProduct->save();

            $stockTakeQueries->setUpdatedAt($stockTakeProduct->stock_take_id);
        }
    }
}
