<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionFilter;

use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Domains\ProductCollectionFilter\Enums\VariantFilterTypes;
use App\Domains\ProductCollectionFilterAttributeValue\ProductCollectionFilterAttributeValueQueries;
use App\Domains\ProductCollectionFilterType\ProductCollectionFilterTypeQueries;
use App\Models\ProductCollectionFilter;

class ProductCollectionFilterQueries
{
    public function separateByFilter(array $filterData, int $productCollectionId): void
    {
        foreach ($filterData as $data) {
            if (config('app.product_variant')) {
                if ((int) $data['filter_type_id'] === VariantFilterTypes::NAME->value) {
                    $this->addNameFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::CREATED_BY->value) {
                    $this->addCreatedByFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::PRICE->value) {
                    $this->addPriceFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::IS_AVAILABLE_IN_POS->value) {
                    $this->addIsAvailablePosFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value) {
                    $this->addIsAvailableEcomFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::CATEGORY->value) {
                    $this->addCategoriesFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::DEPARTMENT->value) {
                    $this->addDepartmentsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::BRAND->value) {
                    $this->addBrandsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::TAG->value) {
                    $this->addTagsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::TYPE->value) {
                    $this->addTypeFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::SALE_UNIT_SOLD->value) {
                    $this->addSaleUnitSoldFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::SALE_AMOUNT->value) {
                    $this->addSaleAmountFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::ORDER_UNIT_SOLD->value) {
                    $this->addOrderUnitSoldFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::ORDER_AMOUNT->value) {
                    $this->addOrderAmountFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === VariantFilterTypes::ATTRIBUTES->value) {
                    $this->addAttributesFilter($data, $productCollectionId);
                }
            } else {
                if ((int) $data['filter_type_id'] === FilterTypes::NAME->value) {
                    $this->addNameFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::CREATED_BY->value) {
                    $this->addCreatedByFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::PRICE->value) {
                    $this->addPriceFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::IS_AVAILABLE_IN_POS->value) {
                    $this->addIsAvailablePosFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value) {
                    $this->addIsAvailableEcomFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::CATEGORY->value) {
                    $this->addCategoriesFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::SEASON->value) {
                    $this->addSeasonsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::DEPARTMENT->value) {
                    $this->addDepartmentsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::COLOR->value) {
                    $this->addColorsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::SIZE->value) {
                    $this->addSizeFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::BRAND->value) {
                    $this->addBrandsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::STYLE->value) {
                    $this->addStylesFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::TAG->value) {
                    $this->addTagsFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::TYPE->value) {
                    $this->addTypeFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::SALE_UNIT_SOLD->value) {
                    $this->addSaleUnitSoldFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::SALE_AMOUNT->value) {
                    $this->addSaleAmountFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::ORDER_UNIT_SOLD->value) {
                    $this->addOrderUnitSoldFilter($data, $productCollectionId);
                }

                if ((int) $data['filter_type_id'] === FilterTypes::ORDER_AMOUNT->value) {
                    $this->addOrderAmountFilter($data, $productCollectionId);
                }
            }
        }
    }

    public function updateFilter(array $collectionFilterData, int $productCollectionId): void
    {
        $this->deleteFilterCollection($productCollectionId);
        $this->separateByFilter($collectionFilterData, $productCollectionId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_collection_id,filter_type_id,condition_operator_type_id,value';
    }

    public function getBasicColumnName(): string
    {
        return 'id,product_collection_id';
    }

    private function deleteFilterCollection(int $productCollectionId): void
    {
        $filterCollections = ProductCollectionFilter::select('id')
            ->where('product_collection_id', $productCollectionId)
            ->get();

        foreach ($filterCollections as $filterCollection) {
            $this->deleteAllPivotData($filterCollection);
            $filterCollection->delete();
        }
    }

    private function deleteAllPivotData(ProductCollectionFilter $filterCollection): void
    {
        $filterCollection->categories()->detach();
        $filterCollection->seasons()->detach();
        $filterCollection->departments()->detach();
        $filterCollection->colors()->detach();
        $filterCollection->sizes()->detach();
        $filterCollection->brands()->detach();
        $filterCollection->styles()->detach();
        $filterCollection->tags()->detach();
        $filterCollection->types()->delete();

        if (config('app.product_variant')) {
            $filterCollection->attributeValues()->delete();
        }
    }

    private function addNew(array $filterData, int $productCollectionId): ProductCollectionFilter
    {
        return ProductCollectionFilter::create([
            'product_collection_id' => $productCollectionId,
            'filter_type_id' => $filterData['filter_type_id'],
            'condition_operator_type_id' => $filterData['condition_operator_id'] ?? null,
            'value' => $filterData['value'] ?? null,
        ]);
    }

    private function addNameFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['name'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addCreatedByFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['created_by'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addSaleUnitSoldFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['sale_unit_sold'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addSaleAmountFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['sale_amount'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addOrderUnitSoldFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['order_unit_sold'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addOrderAmountFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['order_amount'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addPriceFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['price'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addIsAvailablePosFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['is_available_in_pos'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addIsAvailableEcomFilter(array $filterData, int $productCollectionId): void
    {
        $filterData['value'] = $filterData['is_available_in_ecommerce'];
        $this->addNew($filterData, $productCollectionId);
    }

    private function addCategoriesFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->categories()->sync($filterData['category_ids']);
    }

    private function addAttributesFilter(array $filterData, int $productCollectionId): void
    {
        $productCollectionFilter = $this->addNew($filterData, $productCollectionId);

        $attributes = $filterData['attributes'];
        $productCollectionFilterAttributeValueQueries = resolve(ProductCollectionFilterAttributeValueQueries::class);

        foreach ($attributes as $attribute) {
            $attributeId = $attribute['attribute'];
            foreach ($attribute['attribute_selected_values'] as $attributeValue) {
                $productCollectionFilterAttributeValueQueries->addNew(
                    $productCollectionFilter->id,
                    $attributeId,
                    $attributeValue['id']
                );
            }
        }
    }

    private function addSeasonsFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->seasons()->sync($filterData['season_ids']);
    }

    private function addDepartmentsFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->departments()->sync($filterData['department_ids']);
    }

    private function addColorsFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->colors()->sync($filterData['color_ids']);
    }

    private function addSizeFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->sizes()->sync($filterData['size_ids']);
    }

    private function addBrandsFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->brands()->sync($filterData['brand_ids']);
    }

    private function addStylesFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->styles()->sync($filterData['style_ids']);
    }

    private function addTagsFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $collectionFilter->tags()->sync($filterData['tag_ids']);
    }

    private function addTypeFilter(array $filterData, int $productCollectionId): void
    {
        $collectionFilter = $this->addNew($filterData, $productCollectionId);
        $productCollectionFilterTypeQueries = resolve(ProductCollectionFilterTypeQueries::class);
        foreach ($filterData['type_ids'] as $typeId) {
            $productCollectionFilterTypeQueries->addNew($typeId, $collectionFilter->id);
        }
    }
}
