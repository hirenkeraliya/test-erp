<?php

declare(strict_types=1);

namespace App\Domains\Cashback;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Cashback\DataObjects\CashbackData;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\CashbackPrice\CashbackPriceQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\Size\SizeQueries;
use App\Models\Cashback;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashbackQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->cashbacksQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(CashbackData $cashbackData, int $companyId): void
    {
        $cashbackDetails = $cashbackData->all();
        unset($cashbackDetails['location_ids']);
        unset($cashbackDetails['category_ids']);
        unset($cashbackDetails['product_ids']);
        unset($cashbackDetails['tiers']);

        $cashbackDetails['company_id'] = $companyId;

        $cashback = Cashback::create($cashbackDetails);

        $this->syncStoresCategoriesAndProducts($cashback, $cashbackData);
        $this->syncCashbackPrice($cashback, $cashbackData->tiers);
    }

    public function getByIdWithStoresProductsAndCategories(int $cashbackId, int $companyId): Cashback
    {
        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $cashbackPriceQueries = resolve(CashbackPriceQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Cashback::select(
                'id',
                'exclude_by_type',
                'discount_type_id',
                'discount_value',
                'name',
                'minimum_spend_amount',
                'start_date',
                'end_date'
            )
                ->where('company_id', $companyId)
                ->with(
                    'locations:' . $locationQueries->getBasicColumnNames(),
                    'products:' . $productQueries->getBasicColumnNames(),
                    'categories:' . $categoryQueries->getBasicColumnNames(),
                    'products.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'products.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'cashbackPrices:' . $cashbackPriceQueries->getBasicColumnNames(),
                )
                ->findOrFail($cashbackId);
        }

        return Cashback::select(
            'id',
            'exclude_by_type',
            'discount_type_id',
            'discount_value',
            'name',
            'minimum_spend_amount',
            'start_date',
            'end_date'
        )
            ->where('company_id', $companyId)
            ->with(
                'locations:' . $locationQueries->getBasicColumnNames(),
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'products.color:' . $colorQueries->getBasicColumnNames(),
                'products.size:' . $sizeQueries->getBasicColumnNames(),
                'cashbackPrices:' . $cashbackPriceQueries->getBasicColumnNames(),
            )
            ->findOrFail($cashbackId);
    }

    public function update(CashbackData $cashbackData, int $cashbackId, int $companyId): void
    {
        $cashback = Cashback::select(
            'id',
            'exclude_by_type',
            'discount_type_id',
            'discount_value',
            'name',
            'minimum_spend_amount',
            'start_date',
            'end_date'
        )
            ->where('company_id', $companyId)
            ->findOrFail($cashbackId);

        $cashbackDetails = $cashbackData->all();
        unset($cashbackDetails['location_ids']);
        unset($cashbackDetails['category_ids']);
        unset($cashbackDetails['product_ids']);
        unset($cashbackDetails['tiers']);

        $cashback->update($cashbackDetails);

        $this->syncStoresCategoriesAndProducts($cashback, $cashbackData);
        $this->syncCashbackPrice($cashback, $cashbackData->tiers);
    }

    public function getListForPosWithRelatedData(Location $location, ?string $afterUpdatedAt = null): Collection
    {
        $locationQueries = resolve(LocationQueries::class);
        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();
        $cashbackPriceQueries = resolve(CashbackPriceQueries::class);

        return Cashback::select(
            'id',
            'exclude_by_type',
            'discount_type_id',
            'discount_value',
            'name',
            'minimum_spend_amount',
            'start_date',
            'end_date'
        )
            ->with(
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'cashbackPrices:' . $cashbackPriceQueries->getBasicColumnNames(),
            )
            ->where('company_id', $location->company_id)
            ->whereHas('locations', $locationQueries->filterById($location->id, LocationTypes::STORE->value))
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->where('start_date', '<=', now()->format('Y-m-d'))
                    ->where('end_date', '>=', now()->format('Y-m-d'));
            })
            ->get();
    }

    public function getByIdWithRelations(int $cashbackId, int $companyId): Cashback
    {
        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();
        $locationQueries = new LocationQueries();
        $cashbackPriceQueries = new CashbackPriceQueries();

        return Cashback::select(
            'id',
            'exclude_by_type',
            'name',
            'discount_type_id',
            'discount_value',
            'minimum_spend_amount',
            'start_date',
            'end_date'
        )
            ->with(
                'products:' . $productQueries->getBasicColumnNames(),
                'categories:' . $categoryQueries->getBasicColumnNames(),
                'locations:' . $locationQueries->getBasicColumnNames(),
                'cashbackPrices:' . $cashbackPriceQueries->getBasicColumnNames(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($cashbackId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id';
    }

    public function getBasicColumnNamesForPos(): string
    {
        return 'id,name';
    }

    public function removeSelectedProducts(array $cashbackData, int $companyId): void
    {
        /** @var Cashback $cashback */
        $cashback = Cashback::select('id')->where('company_id', $companyId)->findOrFail($cashbackData['id']);
        $cashback->products()->detach();
    }

    public function getByIdWithCashbackProducts(int $cashbackId, int $companyId): Cashback
    {
        $productQueries = new ProductQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return Cashback::select('id')
                ->with(
                    'products:' . $productQueries->getCommonRelationColumns(),
                    'products.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'products.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                )
                ->where('company_id', $companyId)
                ->findOrFail($cashbackId);
        }

        return Cashback::select('id')
            ->with(
                'products:' . $productQueries->getCommonRelationColumns(),
                'products.color:' . $colorQueries->getBasicColumnNames(),
                'products.size:' . $sizeQueries->getBasicColumnNames(),
            )
            ->where('company_id', $companyId)
            ->findOrFail($cashbackId);
    }

    public function getCashbacksExport(array $filterData, int $companyId): Collection
    {
        return $this->cashbacksQuery($filterData, $companyId)->get();
    }

    public function updateProductIdsInCashbackProductPivot(int $oldProductId, int $newProductId): void
    {
        DB::table('cashback_product')
            ->where('product_id', $oldProductId)
            ->update([
                'product_id' => $newProductId,
            ]);
    }

    public function getCashbacksForApplication(array $filteredData, int $companyId): LengthAwarePaginator
    {
        $locationQueries = new LocationQueries();

        return Cashback::query()
            ->select(
                'id',
                'exclude_by_type',
                'name',
                'minimum_spend_amount',
                'discount_type_id',
                'discount_value',
                'start_date',
                'end_date'
            )
            ->where('company_id', $companyId)
            ->when($filteredData['selected_date'], function ($query) use ($filteredData): void {
                $query->where('start_date', '<=', $filteredData['selected_date'])
                    ->where('end_date', '>=', $filteredData['selected_date']);
            })
            ->when($filteredData['location_ids'], function ($query) use ($locationQueries, $filteredData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filteredData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when(null !== $filteredData['search_text'], function ($query) use ($filteredData): void {
                $query->where('name', 'like', '%' . $filteredData['search_text'] . '%');
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })->paginate($filteredData['per_page']);
    }

    public function getCashbacksStoreWiseForApplication(int $companyId, int $locationId): Collection
    {
        return Cashback::query()
            ->select(
                'id',
                'exclude_by_type',
                'name',
                'minimum_spend_amount',
                'discount_type_id',
                'discount_value',
                'start_date',
                'end_date'
            )
            ->where('company_id', $companyId)
            ->whereHas('locations', function ($query) use ($locationId): void {
                $query->select('id')->where('id', $locationId);
            })
            ->get();
    }

    private function cashbacksQuery(array $filterData, int $companyId): Builder
    {
        $locationQueries = new LocationQueries();
        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);

        return Cashback::query()
            ->select(
                'id',
                'exclude_by_type',
                'name',
                'minimum_spend_amount',
                'start_date',
                'end_date',
                'discount_type_id',
                'discount_value',
                'company_id'
            )
            ->with([
                'saleDiscountCashback:' . $saleDiscountQueries->getBasicColumnNames(),
                'saleItemDiscountCashback:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'company:' . $companyQueries->getBasicColumnNames(),
                'company.defaultCountry:' . $countryQueries->getColumnId(),
                'company.defaultCountry.currency:' . $currencyQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereIntegerInRaw(
                            'exclude_by_type',
                            ExcludeByTypes::getMatchingCases($filterData['search_text'])
                        )
                        ->orWhereAny([
                            'discount_value',
                            'minimum_spend_amount',
                        ], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('start_date', '>=', $filterData['date_range'][0])
                    ->where('end_date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($locationQueries, $filterData): void {
                $query->whereHas(
                    'locations',
                    $locationQueries->filterByIds((array) $filterData['location_ids'], LocationTypes::STORE->value)
                );
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function syncStoresCategoriesAndProducts(Cashback $cashback, CashbackData $cashbackData): void
    {
        $cashback->locations()->sync($cashbackData->location_ids);

        $cashback->categories()->detach();
        $cashback->products()->detach();

        if (
            $cashbackData->product_ids
            && $cashbackData->exclude_by_type === ExcludeByTypes::PRODUCTS->value
        ) {
            $cashback->products()->attach($cashbackData->product_ids);

            return;
        }

        $categoryIds = $cashbackData->category_ids ?? [];
        $cashback->categories()->attach($categoryIds);
    }

    private function syncCashbackPrice(Cashback $cashback, ?array $cashbackPrices): void
    {
        $cashbackPriceQueries = resolve(CashbackPriceQueries::class);
        $cashbackPriceQueries->delete($cashback);
        if (null === $cashbackPrices) {
            return;
        }

        if ([] === $cashbackPrices) {
            return;
        }

        foreach ($cashbackPrices as $cashbackPrice) {
            $data = [
                'cashback_id' => $cashback->id,
                'condition_operator_type_id' => $cashbackPrice['condition_operator_type_id'],
                'amount' => $cashbackPrice['amount'],
            ];
            $cashbackPriceQueries->addNew($data);
        }
    }
}
