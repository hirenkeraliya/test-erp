<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Domains\ProductCollectionFilter\Enums\VariantFilterTypes;
use App\Domains\ProductCollectionFilter\ProductCollectionFilterQueries;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\ProductCollection;
use App\Models\ProductCollectionFilter;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->productCollection = ProductCollection::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABC',
        'number_of_products' => 0,
        'pending_products' => 0,
        'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
        'last_sync_at' => now()->format('Y-m-d H:i:s'),
        'status' => true,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => Admin::factory()->create()->id,
    ]);

    $this->productCollectionFilter = ProductCollectionFilter::factory()->create([
        'product_collection_id' => $this->productCollection->id,
        'filter_type_id' => FilterTypes::CATEGORY->value,
        'condition_operator_type_id' => ConditionOperatorTypes::EQUAL->value,
    ]);
});

test(
    'call separateByFilter method add the filter collections when product variant is',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $collectionFilterData = [
            [
                'filter_type_id' => $productVariant ? VariantFilterTypes::CATEGORY->value : FilterTypes::CATEGORY->value,
                'condition_operator_id' => ConditionOperatorTypes::EQUAL->value,
                'category_ids' => $category->pluck('id')->toArray(),
            ],
            [
                'filter_type_id' => $productVariant ? VariantFilterTypes::BRAND->value : FilterTypes::BRAND->value,
                'condition_operator_id' => ConditionOperatorTypes::EQUAL->value,
                'brand_ids' => $brand->pluck('id')->toArray(),
            ],
        ];

        $productCollectionFilterQueries = resolve(ProductCollectionFilterQueries::class);
        $productCollectionFilterQueries->separateByFilter($collectionFilterData, $this->productCollection->id);

        $this->assertDatabaseHas('product_collection_filters', [
            'product_collection_id' => $this->productCollection->id,
            'filter_type_id' => $collectionFilterData[0]['filter_type_id'],
            'condition_operator_type_id' => $collectionFilterData[0]['condition_operator_id'],
        ]);

        $this->assertDatabaseHas('product_collection_filters', [
            'product_collection_id' => $this->productCollection->id,
            'filter_type_id' => $collectionFilterData[1]['filter_type_id'],
            'condition_operator_type_id' => $collectionFilterData[1]['condition_operator_id'],
        ]);

        $this->assertDatabaseHas('category_product_collection_filter', [
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('brand_product_collection_filter', [
            'brand_id' => $brand->id,
        ]);
    }
)->with([[true], [false]]);

test(
    'call updateFilter method update the filter collection when product variant is',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $collectionFilterData = [
            [
                'filter_type_id' => $productVariant ? VariantFilterTypes::CATEGORY->value : FilterTypes::CATEGORY->value,
                'condition_operator_id' => ConditionOperatorTypes::EQUAL->value,
                'category_ids' => $category->pluck('id')->toArray(),
            ],
            [
                'filter_type_id' => $productVariant ? VariantFilterTypes::BRAND->value : FilterTypes::BRAND->value,
                'condition_operator_id' => ConditionOperatorTypes::EQUAL->value,
                'brand_ids' => $brand->pluck('id')->toArray(),
            ],
        ];

        $productCollectionFilterQueries = resolve(ProductCollectionFilterQueries::class);
        $productCollectionFilterQueries->updateFilter($collectionFilterData, $this->productCollection->id);

        $this->assertDatabaseMissing('product_collection_filters', [
            'id' => $this->productCollectionFilter->id,
        ]);

        $this->assertDatabaseHas('product_collection_filters', [
            'product_collection_id' => $this->productCollection->id,
            'filter_type_id' => $collectionFilterData[1]['filter_type_id'],
            'condition_operator_type_id' => $collectionFilterData[1]['condition_operator_id'],
        ]);

        $this->assertDatabaseHas('category_product_collection_filter', [
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('brand_product_collection_filter', [
            'brand_id' => $brand->id,
        ]);
    }
)->with([[true], [false]]);
