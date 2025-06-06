<?php

declare(strict_types=1);

namespace App\Domains\DreamPriceProduct;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DreamPriceProductQueries
{
    public function addNew(array $dreamPriceProductData): DreamPriceProduct
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPriceProduct = DreamPriceProduct::create($dreamPriceProductData);

        $dreamPrice = $dreamPriceQueries->getDreamPriceById($dreamPriceProduct->dream_price_id);
        $dreamPriceQueries->setUpdatedAt($dreamPrice);

        return $dreamPriceProduct;
    }

    public function delete(DreamPrice $dreamPrice): void
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $dreamPrice->dreamPriceProducts()->delete();
        $dreamPriceQueries->setUpdatedAt($dreamPrice);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,dream_price_id,price';
    }

    public function getDreamPriceColumn(): string
    {
        return 'id,dream_price_id,product_id';
    }

    public function getByIdWithProduct(int $dreamPriceId): Collection
    {
        $productQueries = new ProductQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return DreamPriceProduct::query()
                ->select('id', 'product_id', 'dream_price_id', 'price')
                ->with([
                    'product:' . $productQueries->getColumnsForInventoryReports(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->where('dream_price_id', $dreamPriceId)
                ->get();
        }

        return DreamPriceProduct::query()
            ->select('id', 'product_id', 'dream_price_id', 'price')
            ->with([
                'product:' . $productQueries->getColumnsForInventoryReports(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->where('dream_price_id', $dreamPriceId)
            ->get();
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        $dreamPriceProducts = DreamPriceProduct::query()
            ->select('id', 'dream_price_id', 'product_id')
            ->whereHas('dreamPrice', $dreamPriceQueries->filterByCompany($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($dreamPriceProducts as $dreamPriceProduct) {
            $dreamPriceProduct->product_id = $newProductId;
            $dreamPriceProduct->save();
        }
    }

    public function getDreamPriceProduct(array $filteredData): LengthAwarePaginator
    {
        return DreamPriceProduct::query()
            ->select('product_id', 'price')
            ->where('dream_price_id', $filteredData['dream_price_id'])
            ->when($filteredData['after_updated_at'], function ($query) use ($filteredData): void {
                $query->where('updated_at', '>=', $filteredData['after_updated_at']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function getFirstForEcommerceSync(int $saleChannelId, int $companyId): ?DreamPriceProduct
    {
        /* @phpstan-ignore-next-line */
        return $this->dreamPriceProductForEcommerceSync($companyId, $saleChannelId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $saleChannelId, int $companyId): ?DreamPriceProduct
    {
        /* @phpstan-ignore-next-line */
        return $this->dreamPriceProductForEcommerceSync($companyId, $saleChannelId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getDreamPriceProductEcommerceChannelByStartAndEndId(
        int $saleChannelId,
        int $startId,
        int $endId,
        int $companyId
    ): Collection {
        return $this->dreamPriceProductForEcommerceSync($companyId, $saleChannelId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->groupBy('product_id')
            ->get();
    }

    public function getByDreamPriceId(int $dreamPriceId): Collection
    {
        return DreamPriceProduct::query()
            ->select('id', 'dream_price_id', 'product_id', 'price')
            ->where('dream_price_id', $dreamPriceId)
            ->get();
    }

    private function dreamPriceProductForEcommerceSync(int $companyId, int $saleChannelId): Builder
    {
        $dreamPriceQueries = resolve(DreamPriceQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productChannelReferenceQueries = resolve(ProductChannelReferenceQueries::class);

        return DreamPriceProduct::query()
            ->select(...explode(',', $this->getBasicColumnNames()))
            ->with([
                'dreamPrice:' . $dreamPriceQueries->getBasicColumnNames(),
                'product:' . $productQueries->getProductNameColumn(),
                'product.productChannelReferences:' . $productChannelReferenceQueries->getBasicColumnNames(),
            ])
            ->orderBy('price')
            ->whereHas('dreamPrice', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->where('is_available_in_ecommerce', true)
                    ->where('start_date', '<=', now()->startOfDay()->format('Y-m-d'))
                    ->where('end_date', '>=', now()->endOfDay()->format('Y-m-d'))
                    ->where('status', true);
            })
            ->whereHas('product.productChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            });
    }
}
