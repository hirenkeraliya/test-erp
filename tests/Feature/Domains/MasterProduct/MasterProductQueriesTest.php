<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\DataObjects\MasterProductImageUploadData;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Company;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
        'creator_can_approve_draft_product' => true,
    ]);

    $this->masterProductA = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    $this->productA = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236',
        'upc' => 'UPC',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $this->masterProductA->id,
    ]);

    $this->masterProductQueries = new MasterProductQueries();
});

test('Only active master products can be fetched', function (): void {
    $response = $this->masterProductQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'status' => null,
        'batch' => null,
        'date_range' => null,
        'product_type_id' => null,
        'article_numbers' => null,
        'brand_ids' => null,
        'category_ids' => null,
        'department_ids' => null,
        'product_sync_type_id' => null,
    ], $this->companyA->id);
    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->masterProductA->name)
        ->toHaveKey('code', $this->masterProductA->code);
});

test('a master product can be added', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $newMasterProductRecord = MasterProduct::factory()->make([
        'company_id' => $this->companyA->id,
    ])->toArray();

    $category = Category::factory()->create([
        'company_id' => $this->companyA->id,
    ]);
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $admin = Admin::factory()->create();

    $companyId = $newMasterProductRecord['company_id'];
    $newMasterProductRecord['images'] = [];
    $newMasterProductRecord['videos'] = [];
    $newMasterProductRecord['thumbnail'] = $uploadedFile;
    $newMasterProductRecord['category_ids'] = [$category->id];
    $newMasterProductRecord['tag_ids'] = [$tag->id];
    $newMasterProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($newMasterProductRecord['company_id']);

    $this->masterProductQueries->addNew(new MasterProductData(...$newMasterProductRecord), $companyId, $admin);

    unset($newMasterProductRecord['images'], $newMasterProductRecord['category_ids'], $newMasterProductRecord['tag_ids'], $newMasterProductRecord['is_non_selling_item'], $newMasterProductRecord['is_available_in_pos'], $newMasterProductRecord['is_available_in_ecommerce'], $newMasterProductRecord['thumbnail'], $newMasterProductRecord['videos']);

    $this->assertDatabaseHas('master_products', $newMasterProductRecord);
    $this->assertDatabaseHas('master_product_tag', [
        'tag_id' => $tag->id,
    ]);
    $this->assertDatabaseHas('category_master_product', [
        'category_id' => $category->id,
        'sort_order' => 0,
    ]);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::MASTER_PRODUCT->name,
        'collection_name' => 'thumbnail',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A master product can be fetched with media categories and tags', function (): void {
    $response = $this->masterProductQueries->getByIdWithMediaCategoriesAndTags(
        $this->masterProductA->id,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('name', $this->masterProductA->name)
        ->toHaveKey('code', $this->masterProductA->code);
});

test('getByIdWithMediaCategoriesAndTagsAndStatuses method call and return proper response', function (): void {
    $response = $this->masterProductQueries->getByIdWithMediaCategoriesAndTagsAndStatuses(
        $this->masterProductA->id,
        $this->companyA->id
    );
    expect($response->toArray())
        ->toHaveKey('name', $this->masterProductA->name)
        ->toHaveKey('code', $this->masterProductA->code);
});

test('check product status is draft return true', function (): void {
    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'status' => Statuses::DRAFT->value,
    ]);

    $response = $this->masterProductQueries->checkDraftProduct($masterProduct->id, $this->companyA->id);

    expect($response)->toBeTrue();
});

test('updateStatus method call and update status', function (): void {
    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'status' => Statuses::DRAFT->value,
    ]);

    $this->masterProductQueries->updateStatus($masterProduct->id, $this->companyA->id);

    $this->assertDatabaseHas('master_products', [
        'id' => $masterProduct->id,
        'status' => Statuses::ACTIVE->value,
    ]);
});

test('A  master product can be updated', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $newMasterProductRecord = MasterProduct::factory()->make([
        'company_id' => $this->companyA->id,
    ])->toArray();

    unset($newMasterProductRecord['company_id']);

    $newMasterProductRecord['images'] = [];
    $newMasterProductRecord['videos'] = [];
    $newMasterProductRecord['thumbnail'] = $uploadedFile;
    $newMasterProductRecord['category_ids'] = [];
    $newMasterProductRecord['tag_ids'] = [];
    $newMasterProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;

    $this->masterProductQueries->update(
        new MasterProductData(...$newMasterProductRecord),
        $this->masterProductA->id,
        $this->companyA->id
    );

    unset($newMasterProductRecord['images'], $newMasterProductRecord['category_ids'], $newMasterProductRecord['tag_ids'], $newMasterProductRecord['upc'], $newMasterProductRecord['unit_of_measure_id'], $newMasterProductRecord['is_non_selling_item'], $newMasterProductRecord['is_available_in_pos'], $newMasterProductRecord['is_available_in_ecommerce'], $newMasterProductRecord['videos'], $newMasterProductRecord['thumbnail']);

    $this->assertDatabaseHas('master_products', $newMasterProductRecord);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::MASTER_PRODUCT->name,
        'collection_name' => 'thumbnail',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A master product can be fetched', function (): void {
    $response = $this->masterProductQueries->getById($this->masterProductA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->masterProductA->name)
        ->toHaveKey('code', $this->masterProductA->code);
});

test('A master product can be upload image', function (): void {
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $this->masterProductQueries->uploadImage(new MasterProductImageUploadData(...[
        'master_product_id' => $this->masterProductA->id,
        'image' => $uploadedFile,
    ]), $this->companyA->id);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::MASTER_PRODUCT->name,
        'collection_name' => 'thumbnail',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('it remove master product image', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $masterProduct->addMedia($uploadedFile)->toMediaCollection('images');

    $this->masterProductQueries->removeMasterProductImage($masterProduct->id, 1);

    $this->assertDatabaseMissing('media', [
        'model_type' => $masterProduct::class,
        'model_id' => $masterProduct->id,
        'collection_name' => 'images',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test(
    'getActiveFilteredRegularMasterProducts method returns collection of products that match with the search text',
    function (): void {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'article_number' => '12346789',
            'name' => 'ABCDEFG',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            'is_non_selling_item' => false,
        ]);

        $response = $this->masterProductQueries->getActiveFilteredRegularMasterProducts([
            'search_text' => 'ABCDEF',
            'number_of_records' => 5,
        ], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $masterProduct->id)
            ->toHaveKey('name', $masterProduct->name);
    }
);

test(
    'getActiveRegularMasterProductsFilteredByNameBrandAndCategory method returns the list of master products filter by brand and category',
    function (): void {
        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'article_number' => '098765434567',
            'name' => 'ABCDEF',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            'is_non_selling_item' => false,
        ]);

        $masterProduct->categories()
            ->attach($category->id, [
                'sort_order' => 0,
            ]);

        $response = $this->masterProductQueries->getActiveRegularMasterProductsFilteredByNameBrandAndCategory([
            'search_text' => 'ABCDEF',
            'brand_id' => $masterProduct->brand_id,
            'category_id' => $category->id,
        ], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $masterProduct->id)
            ->toHaveKey('name', $masterProduct->name)
            ->toHaveKey('brand_id', $masterProduct->brand_id)
            ->toHaveKeys(['brand', 'categories']);
    }
);
test('searchByArticleNumber method returns the list of products', function (): void {
    $response = $this->masterProductQueries->searchByArticleNumber(
        $this->masterProductA->article_number,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('has_batch', $this->masterProductA->has_batch)
        ->toHaveKey('variant_template_id', $this->masterProductA->variant_template_id)
        ->toHaveKey('name', $this->masterProductA->name);
});

test('searchByArticleNumberWithNonInventory method returns the product is non inventory', function (): void {
    $this->masterProductA->is_non_inventory = false;
    $this->masterProductA->save();

    $response = $this->masterProductQueries->searchByArticleNumberWithNonInventory(
        $this->masterProductA->article_number,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('has_batch', $this->masterProductA->has_batch)
        ->toHaveKey('variant_template_id', $this->masterProductA->variant_template_id)
        ->toHaveKey('name', $this->masterProductA->name);
});

test('getAllByCompanyId returns the Products details', function (): void {
    $response = $this->masterProductQueries->getAllByCompanyId($this->companyA->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->masterProductA->id)
        ->toHaveKey('name', $this->masterProductA->name);
});

test('getCompanyActiveRegularMasterProductCount returns the count of Products', function (): void {
    $response = $this->masterProductQueries->getCompanyActiveRegularMasterProductCount($this->companyA->id);

    expect($response)->toBe(1);
});

test(
    'getMasterProductsArticleNumberForEcommerce returns master products with ecommerce enabled variants',
    function (): void {
        $masterProductWithEcommerce = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'article_number' => 'ECO123',
            'status' => Statuses::ACTIVE->value,
        ]);

        Product::factory()->create([
            'company_id' => $this->companyA->id,
            'master_product_id' => $masterProductWithEcommerce->id,
            'is_available_in_ecommerce' => true,
        ]);

        $masterProductWithoutEcommerce = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'article_number' => 'NON-ECO456',
            'status' => Statuses::ACTIVE->value,
        ]);

        Product::factory()->create([
            'company_id' => $this->companyA->id,
            'master_product_id' => $masterProductWithoutEcommerce->id,
            'is_available_in_ecommerce' => false,
        ]);

        $response = $this->masterProductQueries->getMasterProductsArticleNumberForEcommerce($this->companyA->id);

        expect($response)
            ->toHaveCount(1)
            ->first()->toHaveKeys(['id', 'article_number'])
            ->and($response->first()->id)->toBe($masterProductWithEcommerce->id)
            ->and($response->first()->article_number)->toBe($masterProductWithEcommerce->article_number);
    }
);
