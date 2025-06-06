<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Domains\ProductCollectionFilterType\ProductCollectionFilterTypeQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ProductCollection;
use App\Models\ProductCollectionFilter;

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

test('call addNew method add the types filter collections', function (): void {
    $typeId = ProductTypes::REGULAR_PRODUCT->value;
    $productCollectionFilterTypeQueries = resolve(ProductCollectionFilterTypeQueries::class);
    $productCollectionFilterTypeQueries->addNew($typeId, $this->productCollectionFilter->id);

    $this->assertDatabaseHas('product_collection_filter_types', [
        'type_id' => $typeId,
        'product_collection_filter_id' => $this->productCollectionFilter->id,
    ]);
});
