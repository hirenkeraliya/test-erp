<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNoteProduct;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\GoodsReceivedNoteProduct;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class GoodsReceivedNoteProductQueries
{
    public function addNew(
        float $quantity,
        int $goodsReceivedNoteId,
        int $productId,
        ?int $batchId,
        int $purchaseAmountId,
        ?int $derivativeId,
        ?float $inputQuantity,
        ?float $currentDerivateRatio,
        ?int $serialNumberId,
    ): GoodsReceivedNoteProduct {
        return GoodsReceivedNoteProduct::create([
            'goods_received_note_id' => $goodsReceivedNoteId,
            'product_id' => $productId,
            'batch_id' => $batchId,
            'purchase_amount_id' => $purchaseAmountId,
            'unit_of_measure_derivative_id' => $derivativeId,
            'input_quantity' => $inputQuantity,
            'derivative_ratio' => $currentDerivateRatio,
            'quantity' => $quantity,
            'serial_number_id' => $serialNumberId,
        ]);
    }

    public function getByGrnId(int $goodsReceivedNoteId, int $companyId): Collection
    {
        return $this->commonGetByGrnId($goodsReceivedNoteId, $companyId)
                ->get();
    }

    public function getMorphLocationBasicColumns(): string
    {
        return 'id,name';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,goods_received_note_id,product_id,batch_id,purchase_amount_id,quantity,serial_number_id';
    }

    public function getBasicColumnNamesForCustomReport(): string
    {
        return 'id,goods_received_note_id,product_id,purchase_amount_id,quantity,created_at';
    }

    public function getQuantityColumnName(): string
    {
        return 'id,goods_received_note_id,quantity';
    }

    public function getByDateAndLocationWithProductByBrand(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        return GoodsReceivedNoteProduct::query()
            ->select('id', 'goods_received_note_id', 'product_id', 'purchase_amount_id', 'quantity', 'created_at')
            ->with(
                'goodsReceivedNote:' . $goodsReceivedNoteQueries->getLocationColumns(),
                'goodsReceivedNote.location:' . $this->getMorphLocationBasicColumns(),
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'purchaseAmount:' . $purchaseAmountQueries->getLandedCostColumn(),
                'product.brand:' . $brandQueries->getIdAndNameColumnNames(),
            )
            ->whereHas(
                'goodsReceivedNote',
                $goodsReceivedNoteQueries->filterByCompanyAndLocation($companyId, $filterData)
            )
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->when($filterData['brand_ids'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', $productQueries->filterByBrandIds($filterData['brand_ids']));
            })
            ->get();
    }

    public function getByDateAndLocationWithProductByDepartment(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        return GoodsReceivedNoteProduct::query()
            ->select('id', 'goods_received_note_id', 'product_id', 'purchase_amount_id', 'quantity', 'created_at')
            ->with(
                'goodsReceivedNote:' . $goodsReceivedNoteQueries->getLocationColumns(),
                'goodsReceivedNote.location:' . $this->getMorphLocationBasicColumns(),
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'purchaseAmount:' . $purchaseAmountQueries->getLandedCostColumn(),
                'product.department:' . $departmentQueries->getBasicColumnNames(),
            )
            ->whereHas(
                'goodsReceivedNote',
                $goodsReceivedNoteQueries->filterByCompanyAndLocation($companyId, $filterData)
            )
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->when($filterData['department_ids'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', $productQueries->filterByDepartmentIds($filterData['department_ids']));
            })
            ->get();
    }

    public function getProductIdWithQuantity(string $referenceNumber, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return GoodsReceivedNoteProduct::query()
            ->select('id', 'goods_received_note_id', 'product_id', 'quantity')
            ->with(['product:' . $productQueries->getBasicColumnNames()])
            ->where('goods_received_note_id', function ($query) use ($referenceNumber, $companyId): void {
                $query->select('id')
                    ->from('goods_received_notes')
                    ->where('grn_reference', $referenceNumber)
                    ->where('company_id', $companyId);
            })
            ->get();
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        $goodsReceivedNoteProducts = GoodsReceivedNoteProduct::query()
            ->select('id', 'goods_received_note_id', 'product_id')
            ->whereHas('goodsReceivedNote', function ($query) use ($companyId, $goodsReceivedNoteQueries): void {
                $query->where($goodsReceivedNoteQueries->filterByCompany($companyId));
            })
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($goodsReceivedNoteProducts as $goodReceivedNoteProduct) {
            $goodReceivedNoteProduct->product_id = $newProductId;
            $goodReceivedNoteProduct->save();
        }
    }

    public function getByGrnIdForApi(int $companyId, array $filterData): LengthAwarePaginator
    {
        $productQueries = resolve(ProductQueries::class);

        return $this->commonGetByGrnId((int) $filterData['id'], $companyId)
            ->whereHas('product', $productQueries->searchByNameUpcAndArticleNumber($filterData['search_text']))
            ->paginate($filterData['per_page']);
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }

    public function getGoodsReceivedNoteForStockCardPrint(): Closure
    {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);

        return fn ($query) => $query->select('id', 'goods_received_note_id')
            ->with(['goodsReceivedNote:' . $goodsReceivedNoteQueries->getColumnsForStockCardPrint()]);
    }

    public function getGoodsReceivedNoteWithRelation(): Closure
    {
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        return fn ($query) => $query->select('id', 'goods_received_note_id')
            ->with([
                'goodsReceivedNote:' . $goodsReceivedNoteQueries->getColumns(),
                'goodsReceivedNote.location:' . $locationQueries->getBasicColumnNamesOfReport(),
                'goodsReceivedNote.vendor:' . $vendorQueries->getBasicColumnNames(),
            ]);
    }

    private function commonGetByGrnId(int $goodsReceivedNoteId, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return GoodsReceivedNoteProduct::query()
                ->select('id', 'product_id', 'batch_id', 'purchase_amount_id', 'quantity')
                ->whereHas('goodsReceivedNote', $goodsReceivedNoteQueries->filterByCompany($companyId))
                ->with(
                    'product:' . $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'batch:' . $batchQueries->getIdNumberAndExpiryDateColumnNames(),
                    'purchaseAmount:' . $purchaseAmountQueries->getColumnNames()
                )
                ->where('goods_received_note_id', $goodsReceivedNoteId);
        }

        return GoodsReceivedNoteProduct::query()
            ->select('id', 'product_id', 'batch_id', 'purchase_amount_id', 'quantity')
            ->whereHas('goodsReceivedNote', $goodsReceivedNoteQueries->filterByCompany($companyId))
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'batch:' . $batchQueries->getIdNumberAndExpiryDateColumnNames(),
                'purchaseAmount:' . $purchaseAmountQueries->getColumnNames()
            )
            ->where('goods_received_note_id', $goodsReceivedNoteId);
    }
}
