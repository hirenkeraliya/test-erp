<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Product\Enums\Statuses;
use App\Domains\ProductCollection\DataObjects\ProductCollectionData;
use App\Domains\ProductCollection\DataObjects\ProductCollectionImagesData;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Company;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductCollectionFilter;
use App\Models\SaleChannel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

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
});

test('call listQuery method fetch the product collection', function (): void {
    $filterData = [
        'search_text' => 'ABC',
        'per_page' => 10,
    ];

    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $response = $productCollectionQueries->listQuery($filterData, $this->companyA->id);
    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->productCollection->name)
        ->toHaveKey('logical_connector_type_id', $this->productCollection->logical_connector_type_id->value);
});

test(
    'call getPaginatedProductCollectionsForEcommerce method fetch the product collection and product ids',
    function (): void {
        $filterData = [
            'search_text' => 'ABC',
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'per_page' => 10,
            'after_updated_at' => null,
        ];

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $response = $productCollectionQueries->getPaginatedProductCollectionsForEcommerce(
            $filterData,
            $this->companyA->id
        );
        $this->assertEquals(1, $response->total());
        expect($response->getCollection())->toHaveCount(1);
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('id', $this->productCollection->id)
            ->toHaveKey('name', $this->productCollection->name);
    }
);

test('call addNew method add the product collection', function (): void {
    $admin = Admin::factory()->create();

    $productCollectionData = [
        'name' => 'ABCD',
        'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
        'collection_filter_types' => [
            [
                'filter_type_id' => FilterTypes::CATEGORY->value,
                'condition_operator_id' => ConditionOperatorTypes::EQUAL->value,
                'category_ids' => Category::factory(2)->create()->pluck('id')->toArray(),
            ],
        ],
    ];

    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $productCollectionQueries->addNew(
        $admin,
        new ProductCollectionData(...$productCollectionData),
        $this->companyA->id
    );

    $this->assertDatabaseHas('product_collections', [
        'name' => 'ABCD',
        'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
    ]);
});

test('call edit method return product collection', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $response = $productCollectionQueries->edit($this->productCollection->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->productCollection->name)
        ->toHaveKey('logical_connector_type_id', $this->productCollection->logical_connector_type_id->value)
        ->toHaveKey('id', $this->productCollection->id);
});

test('call update method product collection update', function (): void {
    $productCollectionData = [
        'name' => 'Test',
        'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
        'collection_filter_types' => [
            [
                'filter_type_id' => FilterTypes::CATEGORY->value,
                'condition_operator_id' => ConditionOperatorTypes::EQUAL->value,
                'category_ids' => Category::factory(2)->create()->pluck('id')->toArray(),
            ],
        ],
    ];

    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $productCollectionQueries->update(
        new ProductCollectionData(...$productCollectionData),
        $this->productCollection->id
    );

    $this->assertDatabaseHas('product_collections', [
        'id' => $this->productCollection->id,
        'name' => $productCollectionData['name'],
        'logical_connector_type_id' => $productCollectionData['logical_connector_type_id'],
    ]);
});

test('call changeStatus method change the product collection', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $productCollectionQueries->changeStatus($this->productCollection->id);

    $this->assertDatabaseHas('product_collections', [
        'id' => $this->productCollection->id,
        'status' => false,
    ]);
});

test('call delete method delete the product collection', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $productCollectionQueries->delete($this->productCollection->id, $this->companyA->id);

    $this->assertSoftDeleted($this->productCollection);
});

test('call getProductCollections method and return the product collections by company', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $response = $productCollectionQueries->getProductCollections($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productCollection->id);
});

test(
    'call getProductByProductCollectionAndCompany method check product property match with product collection',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::ACTIVE->value,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => true,
            ]);

            $product->master_product_id = $masterProduct->id;
            $product->save();
        }

        if ($productVariant) {
            $masterProduct->categories()->attach($category->id, [
                'sort_order' => 0,
            ]);
        } else {
            $product->categories()->attach($category->id, [
                'sort_order' => 0,
            ]);
        }

        $productCollectionFilter = ProductCollectionFilter::factory()->create([
            'product_collection_id' => $this->productCollection->id,
            'filter_type_id' => FilterTypes::CATEGORY->value,
        ]);

        $productCollectionFilter->categories = collect([$category]);

        $this->productCollection->productCollectionFilter = collect([$productCollectionFilter]);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $response = $productCollectionQueries->getProductByProductCollectionAndCompany(
            $product->id,
            $this->productCollection,
            $this->companyA->id
        );

        expect($response)
            ->toHaveKey('company_id', $this->productCollection->company_id)
            ->toHaveKey('id', $product->id);
    }
)->with([true, false]);

test(
    'call getMatchProducts method check product property match with product collection',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);
        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::ACTIVE->value,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => true,
            ]);

            $product->master_product_id = $masterProduct->id;
            $product->save();
        }

        if ($productVariant) {
            $masterProduct->categories()->attach($category->id, [
                'sort_order' => 0,
            ]);
        } else {
            $product->categories()->attach($category->id, [
                'sort_order' => 0,
            ]);
        }

        $productCollectionFilter = ProductCollectionFilter::factory()->create([
            'product_collection_id' => $this->productCollection->id,
            'filter_type_id' => FilterTypes::CATEGORY->value,
        ]);

        $productCollectionFilter->categories = collect([$category]);

        $this->productCollection->productCollectionFilter = collect([$productCollectionFilter]);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $response = $productCollectionQueries->getMatchProducts($this->productCollection, $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('company_id', $this->productCollection->company_id)
            ->toHaveKey('id', $product->id);
    }
)->with([true, false]);

test('call getProductCollectionById method return product collection', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $response = $productCollectionQueries->getProductCollectionById($this->productCollection->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->productCollection->id);
});

test('call getByIdWithRelation method return product collection with import record relation', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);

    $response = $productCollectionQueries->getByIdWithRelation($this->productCollection->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->productCollection->id)
        ->toHaveKey('import_record');
});

test('A getProductCollectionByIdAndCompanyId method call and return proper response', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $response = $productCollectionQueries->getProductCollectionByIdAndCompanyId(
        $this->productCollection->id,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('name', $this->productCollection->name);
});

test('A uploadImages method call and upload the images', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $productCollectionQueries = resolve(ProductCollectionQueries::class);

    $productCollectionImageData = [
        'square_image' => $uploadedFile,
        'portrait_images' => [],
        'landscape_images' => [],
    ];

    $response = $productCollectionQueries->uploadImages(
        $this->productCollection,
        new ProductCollectionImagesData(...$productCollectionImageData)
    );

    expect($response)->toBeNull();
});

test('A getById method call and return product collection object', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);

    $response = $productCollectionQueries->getById($this->productCollection->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->productCollection->id)
        ->toHaveKey('name', $this->productCollection->name);
});

test('it removes an image from the product collection', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $media = $this->productCollection->addMedia($uploadedFile)->toMediaCollection('landscape_images');

    $productCollectionQueries->removeImage($this->productCollection->id, $media->id, 'landscape_images');

    $this->assertDatabaseMissing('media', [
        'model_type' => 'PRODUCT_COLLECTION',
        'model_id' => $this->productCollection->id,
        'collection_name' => 'landscape_images',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('it called the getFilteredProductCollectionsByCompanyId method to get the collections', function (): void {
    $searchText = 'ABC';
    $productCollectionQueries = resolve(ProductCollectionQueries::class);

    $response = $productCollectionQueries->getFilteredProductCollectionsByCompanyId($searchText, $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productCollection->id)
        ->toHaveKey('name', $this->productCollection->name);
});

test('it called the getPaginatedProductCollectionsForPos method to get the collections', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);

    $filteredData = [
        'per_page' => 10,
        'sort_by' => null,
        'sort_direction' => 'desc',
        'after_updated_at' => null,
    ];

    $response = $productCollectionQueries->getPaginatedProductCollectionsForPos($filteredData, $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productCollection->id)
        ->toHaveKey('name', $this->productCollection->name);
});

test('Get Product collection name for export PDF headers', function (): void {
    $productCollectionQueries = resolve(ProductCollectionQueries::class);

    $response = $productCollectionQueries->getProductCollectionNameForFilter([$this->productCollection->id]);

    $this->assertIsString($response);
});

test(
    'validateProductCollectionSaleChannelMatch returns true when product collection and sale channel match',
    function (): void {
        $productCollectionQueries = resolve(ProductCollectionQueries::class);

        $saleChannel = SaleChannel::factory()->create();
        $this->productCollection->saleChannels()->attach($saleChannel->id);

        $result = $productCollectionQueries->validateProductCollectionSaleChannelMatch(
            $this->productCollection,
            $saleChannel
        );

        expect($result)->toBeTrue();
    }
);
