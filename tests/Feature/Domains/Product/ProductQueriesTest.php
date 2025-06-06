<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Azentio\DataObjects\AzentioItemData;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\InventoryUpdate\Enums\StockMovementFilters;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\DataObjects\ProductDataForIntegration;
use App\Domains\Product\DataObjects\ProductImageUploadByArticleNumberData;
use App\Domains\Product\DataObjects\ProductImageUploadData;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Batch;
use App\Models\BoxProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\GoodsReceivedNote;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\MergeProductTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductCollectionProduct;
use App\Models\ProductVariantValue;
use App\Models\Sale;
use App\Models\SaleChannel;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Size;
use App\Models\StoreManager;
use App\Models\Style;
use App\Models\Tag;
use App\Models\Template;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
        'creator_can_approve_draft_product' => true,
    ]);

    $this->productA = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236',
        'upc' => 'UPC',
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    $this->productB = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'code',
        'upc' => 'UPC1',
        'status' => Statuses::ARCHIVED->value,
        'article_number' => '12345',
    ]);

    $this->productQueries = new ProductQueries();
});

test('Products can be searched with product variant is false', function (): void {
    Config::set('app.product_variant', false);

    Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'XYZ',
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $response = $this->productQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'product_collection_ids' => null,
        'product_sync_type_id' => ProductSyncTypes::ALL_PRODUCT->value,
        'attributes' => null,
    ], $this->companyA->id);
    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('code', $this->productA->code)
        ->toHaveKey('brand_id', $this->productA->brand_id)
        ->toHaveKey('color_id', $this->productA->color_id)
        ->toHaveKey('size_id', $this->productA->size_id)
        ->toHaveKey('department_id', $this->productA->department_id)
        ->toHaveKey('style_id', $this->productA->style_id)
        ->toHaveKey('upc', $this->productA->upc)
        ->toHaveKey('article_number', $this->productA->article_number)
        ->toHaveKey('retail_price', $this->productA->retail_price)
        ->toHaveKey('purchase_cost', $this->productA->purchase_cost)
        ->toHaveKey('status', $this->productA->status)
        ->toHaveKey('created_by_id', $this->productA->created_by_id)
        ->toHaveKey('created_by_type', $this->productA->created_by_type)
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at');
});

test('Products can be searched with product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
        'master_product_id' => $masterProduct->id,
    ]);

    $product->masterProduct = $masterProduct;

    $response = $this->productQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'product_collection_ids' => null,
        'product_sync_type_id' => ProductSyncTypes::ALL_PRODUCT->value,
        'attributes' => [],
    ], $this->companyA->id);
    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKey('code', $product->code)
        ->toHaveKey('upc', $product->upc)
        ->toHaveKey('retail_price', $product->retail_price)
        ->toHaveKey('purchase_cost', $product->purchase_cost)
        ->toHaveKey('status', $product->status)
        ->toHaveKey('created_by_id', $product->created_by_id)
        ->toHaveKey('created_by_type', $product->created_by_type)
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at')
        ->toHaveKey('master_product');
});

test('Only active products can be fetched when product variant', function (bool $productVariant): void {
    Config::set('app.product_variant', $productVariant);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);
    }

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
        'code' => $productVariant ? '8898998' : '12132465465',
        'status' => Statuses::ACTIVE->value,
        'master_product_id' => $productVariant ? $masterProduct->id : null,
    ]);

    if ($productVariant) {
        $product->masterProduct = $masterProduct;
    }

    $response = $this->productQueries->listQuery([
        'search_text' => $product->compound_product_name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'product_collection_ids' => null,
        'product_sync_type_id' => ProductSyncTypes::ALL_PRODUCT->value,
        'attributes' => [],
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);

    if ($productVariant) {
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKey('code', $product->code)
            ->toHaveKey('master_product_id', $masterProduct->id);
    } else {
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKey('code', $product->code);
    }
})->with([[true], [false]]);

test('get Products Pending Approval in collection when product variant', function (bool $productVariant): void {
    $admin = Admin::factory()->create();
    $admin2 = Admin::factory()->create();

    Config::set('app.product_variant', $productVariant);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);
    }

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
        'code' => '3456',
        'status' => Statuses::DRAFT->value,
        'created_by_id' => $admin2->id,
        'created_by_type' => ModelMapping::ADMIN->name,
        'master_product_id' => $productVariant ? $masterProduct->id : null,
    ]);

    if ($productVariant) {
        $product->masterProduct = $masterProduct;
    }

    $response = $this->productQueries->getDraftProductIdsByExceptLoginUser([
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'employee_id' => null,
        'attributes' => null,
    ], $this->companyA->id, $admin->id);

    $this->assertEquals(1, $response->count());
    expect($response)->toBeInstanceOf(Collection::class);
})->with([[true], [false]]);

test(
    'get Products Pending Approval company wise in collection when product variant',
    function (bool $productVariant): void {
        $admin2 = Admin::factory()->create();

        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'ABCD',
            'code' => '3456',
            'status' => Statuses::DRAFT->value,
            'created_by_id' => $admin2->id,
            'created_by_type' => ModelMapping::ADMIN->name,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $response = $this->productQueries->getDraftProductIdsByCompanyLevel([
            'search_text' => '',
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
            'status' => ProductStatuses::ACTIVE->value,
            'batch' => ProductBatches::ALL->value,
            'date_range' => null,
            'product_type_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'color_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'employee_id' => null,
            'attributes' => null,
        ], $this->companyA->id);

        $this->assertEquals(1, $response->count());
        expect($response)->toBeInstanceOf(Collection::class);
    }
)->with([[true], [false]]);

test('Only Products Pending Approval can be fetched when product variant', function (bool $productVariant): void {
    Config::set('app.product_variant', $productVariant);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);
    }

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
        'code' => '3456',
        'status' => Statuses::DRAFT->value,
        'master_product_id' => $productVariant ? $masterProduct->id : null,
    ]);

    if ($productVariant) {
        $product->masterProduct = $masterProduct;
    }

    $response = $this->productQueries->fetchDraftList([
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'employee_id' => null,
        'attributes' => null,
    ], $this->companyA->id);
    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);

    if ($productVariant) {
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKey('code', $product->code)
            ->toHaveKey('master_product_id', $masterProduct->id);
    } else {
        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKey('code', $product->code);
    }
})->with([[true], [false]]);

test('a product can be added', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $color = Color::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $size = Size::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $newProductRecord = Product::factory()->make([
        'company_id' => $this->companyA->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
    ])->toArray();

    $category = Category::factory()->create([
        'company_id' => $this->companyA->id,
    ]);
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $admin = Admin::factory()->create();

    $companyId = $newProductRecord['company_id'];
    $newProductRecord['images'] = [];
    $newProductRecord['videos'] = [];
    $newProductRecord['thumbnail'] = $uploadedFile;
    $newProductRecord['category_ids'] = [$category->id];
    $newProductRecord['tag_ids'] = [$tag->id];
    $newProductRecord['tiers'] = [];
    $newProductRecord['assembly_child_products'] = [];
    $newProductRecord['boxes'] = [];
    $newProductRecord['attached_templates'] = [];
    $newProductRecord['custom_field_values'] = null;
    $newProductRecord['retail_planning_hierarchy_id'] = null;
    $newProductRecord['warranty_month'] = null;
    $newProductRecord['vendor_id'] = null;
    $newProductRecord['is_warranty'] = false;
    $newProductRecord['original_created_at'] = null;
    $newProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    $newProductRecord['width'] = 0;
    $newProductRecord['height'] = 0;
    $newProductRecord['weight'] = 0;
    unset($newProductRecord['company_id']);

    $this->productQueries->addNew(new ProductData(...$newProductRecord), $companyId, $admin);

    unset($newProductRecord['images'], $newProductRecord['category_ids'], $newProductRecord['tag_ids'], $newProductRecord['is_non_selling_item'], $newProductRecord['is_available_in_pos'], $newProductRecord['is_available_in_ecommerce'], $newProductRecord['thumbnail'], $newProductRecord['videos'], $newProductRecord['tiers'], $newProductRecord['boxes'], $newProductRecord['assembly_child_products'], $newProductRecord['custom_field_values'], $newProductRecord['attached_templates']);
    $newProductRecord['compound_product_name'] = $newProductRecord['name'] . ' ' . $color->getName() . ' ' . $size->getName();

    $this->assertDatabaseHas('products', $newProductRecord);
    $this->assertDatabaseHas('product_tag', [
        'tag_id' => $tag->id,
    ]);
    $this->assertDatabaseHas('category_product', [
        'category_id' => $category->id,
        'sort_order' => 0,
    ]);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::PRODUCT->name,
        'collection_name' => 'thumbnail',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('it remove product image', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'XYZ',
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $product->addMedia($uploadedFile)->toMediaCollection('images');

    $this->productQueries->removeProductImage($product->id, 1);

    $this->assertDatabaseMissing('media', [
        'model_type' => $product::class,
        'model_id' => $product->id,
        'collection_name' => 'images',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A product can be fetched with media categories and tags', function (): void {
    $response = $this->productQueries->getByIdWithMediaCategoriesAndTags($this->productA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('code', $this->productA->code);
});

test('A product can be fetched with media categories and tags for Products Pending Approval', function (): void {
    $draftProduct = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236456',
        'upc' => 'UPC456',
        'article_number' => '1234',
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'status' => Statuses::DRAFT->value,
    ]);

    $response = $this->productQueries->getByIdWithMediaCategoriesAndTagsForDraftProduct(
        $draftProduct->id,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('name', $draftProduct->name)
        ->toHaveKey('code', $draftProduct->code);
});

test('check product status is draft return true', function (): void {
    $draftProduct = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236000',
        'upc' => 'UPC790',
        'article_number' => '1234',
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'status' => Statuses::DRAFT->value,
    ]);

    $response = $this->productQueries->checkDraftProduct($draftProduct->id, $this->companyA->id);

    expect($response)->toBeTrue();
});

test('check product status is not draft return false', function (): void {
    $response = $this->productQueries->checkDraftProduct($this->productA->id, $this->companyA->id);

    expect($response)->toBeFalse();
});

test('A inactive product cannot be fetched with media categories and tags', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'status' => Statuses::ARCHIVED->value,
    ]);

    $this->productQueries->getByIdWithMediaCategoriesAndTags($product->id, $this->companyA->id);
})->throws(ModelNotFoundException::class);

test('A product can be upload image', function (): void {
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $this->productQueries->uploadImage(new ProductImageUploadData(...[
        'image' => $uploadedFile,
        'product_id' => $this->productA->id,
    ]), $this->companyA->id);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::PRODUCT->name,
        'collection_name' => 'thumbnail',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A product can be updated', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $color = Color::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $size = Size::factory()->create([
        'company_id' => $this->companyA->id,
    ]);
    $newProductRecord = Product::factory()->make([
        'color_id' => $color->id,
        'size_id' => $size->id,
    ])->toArray();
    unset($newProductRecord['company_id']);

    $newProductRecord['images'] = [];
    $newProductRecord['videos'] = [];
    $newProductRecord['thumbnail'] = $uploadedFile;
    $newProductRecord['category_ids'] = [];
    $newProductRecord['tag_ids'] = [];
    $newProductRecord['tiers'] = [];
    $newProductRecord['assembly_child_products'] = [];
    $newProductRecord['boxes'] = [];
    $newProductRecord['attached_templates'] = [];
    $newProductRecord['custom_field_values'] = null;
    $newProductRecord['retail_planning_hierarchy_id'] = null;
    $newProductRecord['warranty_month'] = null;
    $newProductRecord['vendor_id'] = null;
    $newProductRecord['is_warranty'] = false;
    $newProductRecord['original_created_at'] = null;

    $newProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    $newProductRecord['width'] = 0;
    $newProductRecord['height'] = 0;
    $newProductRecord['weight'] = 0;

    $this->productQueries->update(new ProductData(...$newProductRecord), $this->productA->id, $this->companyA->id);

    unset($newProductRecord['images'], $newProductRecord['category_ids'], $newProductRecord['tag_ids'], $newProductRecord['upc'], $newProductRecord['unit_of_measure_id'], $newProductRecord['is_non_selling_item'], $newProductRecord['is_available_in_pos'], $newProductRecord['is_available_in_ecommerce'], $newProductRecord['videos'], $newProductRecord['thumbnail'], $newProductRecord['tiers'], $newProductRecord['boxes'], $newProductRecord['assembly_child_products'], $newProductRecord['custom_field_values'], $newProductRecord['attached_templates']);
    $newProductRecord['compound_product_name'] = $newProductRecord['name'] . ' ' . $color->getName() . ' ' . $size->getName();

    $this->assertDatabaseHas('products', $newProductRecord);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::PRODUCT->name,
        'collection_name' => 'thumbnail',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test('A product can be fetched', function (): void {
    $response = $this->productQueries->getById($this->productA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('code', $this->productA->code);
});

test('A draft product can be fetched', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'status' => Statuses::DRAFT->value,
    ]);
    $response = $this->productQueries->getByIdDraftProduct($product->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('id', $product->id)
        ->toHaveKey('master_product_id', $product->master_product_id);
});

test('A getProductByIdAndCompanyId method call and return proper response', function (): void {
    $response = $this->productQueries->getProductByIdAndCompanyId($this->productA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->productA->name);
});

test('getByIdWithUpc method return product', function (): void {
    $response = $this->productQueries->getByIdWithUpc($this->productA->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('upc', $this->productA->upc);
});

test('getActiveProductsByUpc method returns collection', function (): void {
    $response = $this->productQueries->getActiveProductsByUpc([$this->productA->upc], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('upc', $this->productA->upc)
        ->toHaveKey('has_batch', $this->productA->has_batch);
});

test('getActiveInventoryProductByUpcForGRN method returns collection when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->productQueries->getActiveInventoryProductByUpcForGRN(
        (string) $this->productA->upc,
        $this->companyA->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('upc', $this->productA->upc)
        ->toHaveKey('has_batch', $this->productA->has_batch)
        ->toHaveKey('unit_of_measure_id', $this->productA->unit_of_measure_id);
});

test('getActiveInventoryProductByUpcForGRN method returns collection when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A12300000',
        'upc' => '1234567822345',
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $response = $this->productQueries->getActiveInventoryProductByUpcForGRN(
        (string) $product->upc,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKey('upc', $product->upc)
        ->toHaveKey('master_product.has_batch', $product->masterProduct->has_batch)
        ->toHaveKey('master_product.unit_of_measure_id', $product->masterProduct->unit_of_measure_id);
});

test('getActiveInventoryProductsByUpcs method returns collection when product variant false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->productQueries->getActiveInventoryProductsByUpcs(
        [$this->productA->upc],
        $this->companyA->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('upc', $this->productA->upc)
        ->toHaveKey('has_batch', $this->productA->has_batch)
        ->toHaveKeys(['color', 'size', 'compound_product_name']);
});

test('getActiveInventoryProductsByUpcs method returns collection when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236553366',
        'upc' => '12345678',
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $response = $this->productQueries->getActiveInventoryProductsByUpcs([$product->upc], $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKey('upc', $product->upc)
        ->toHaveKey('master_product.has_batch', $product->masterProduct->has_batch)
        ->toHaveKeys(['compound_product_name', 'product_variant_values']);
});

test('A inactive product cannot be fetched', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'status' => Statuses::ARCHIVED->value,
    ]);

    $this->productQueries->getById($product->id, $this->companyA->id);
})->throws(ModelNotFoundException::class);

test('it retrieves a collection of products by their IDs', function (): void {
    $productId = Product::factory()->create()->id;

    $response = $this->productQueries->getByIds([$productId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test('getByCodeAndCompanyId method returns result as expected', function (): void {
    $response = $this->productQueries->getByCodeAndCompanyId($this->productA->code, $this->companyA->id);
    expect($response)
        ->toHaveKeys(['id', 'status']);

    $response = $this->productQueries->getByCodeAndCompanyId('CODEABCDEFGH', $this->companyA->id);
    expect($response)->toBeNull();
});

test('getByUpcAndCompanyId method returns result as expected', function (): void {
    $response = $this->productQueries->getByUpcAndCompanyId($this->productA->upc, $this->companyA->id);
    expect($response)
        ->toHaveKeys(['id', 'status']);

    $response = $this->productQueries->getByUpcAndCompanyId('UPCABCDEFGH', $this->companyA->id);
    expect($response)->toBeNull();
});

test('getByUpcAndCompanyIdForImportMerge method returns result as expected', function (): void {
    $response = $this->productQueries->getByUpcAndCompanyIdForImportMerge($this->productA->upc, $this->companyA->id);
    expect($response)
        ->toHaveKeys(['id', 'name', 'status', 'article_number', 'type_id']);

    $response = $this->productQueries->getByUpcAndCompanyIdForImportMerge('UPCABCDEFGH', $this->companyA->id);
    expect($response)->toBeNull();
});

test('getProductByIdWithRelationsForEcommerce method returns result as expected', function (): void {
    $response = $this->productQueries->getProductByIdWithRelationsForEcommerce($this->productA->id);
    expect($response)
        ->toHaveKeys(
            [
                'id',
                'master_product_id',
                'name',
                'article_number',
                'upc',
                'article_number',
                'company_id',
                'compound_product_name',
                'code',
                'retail_price',
                'online_price',
                'brand_id',
                'description',
                'height',
                'width',
                'weight',
                'status',
                'is_available_in_ecommerce',
                'master_product',
                'sale_channels',
                'product_variant_values',
                'deleted_at',
            ]
        );
});

test(
    'getList method returns the products list',
    function (): void {
        Event::fake();

        $location = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $this->productA->id,
            'stock' => 50,
            'location_id' => $location->id,
        ]);

        $batch = Batch::factory()->create([
            'company_id' => $this->companyA->id,
            'product_id' => $this->productA->id,
            'number' => '123456',
        ]);

        InventoryUnit::factory()->create([
            'inventory_id' => $inventory->id,
            'batch_id' => $batch->id,
            'quantity' => 40,
        ]);

        InventoryUnit::factory()->create([
            'inventory_id' => $inventory->id,
            'batch_id' => $batch->id,
            'quantity' => 10,
        ]);

        $deletedProduct = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'ABCD123456798',
            'code' => 'A123456789',
            'upc' => '123456789',
            'article_number' => '1234',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'is_non_selling_item' => false,
            'is_available_in_pos' => true,
            'is_available_in_ecommerce' => false,
            'deleted_at' => now(),
        ]);

        MergeProductTransaction::factory()->create([
            'old_product_id' => $deletedProduct->id,
            'new_product_id' => $this->productA->id,
        ]);

        $packageType = PackageType::factory()->create()->id;

        BoxProduct::factory()->create([
            'product_id' => $this->productA->id,
            'package_type_id' => $packageType,
        ]);

        $response = $this->productQueries->getList([
            'per_page' => 10,
            'search_text' => null,
            'after_updated_at' => null,
        ], $this->companyA->id, $inventory->location_id);

        expect($response->getCollection()->first()->toArray())
            ->toHaveKey('id', $this->productA->id)
            ->toHaveKey('name', $this->productA->name)
            ->toHaveKey('upc', $this->productA->upc)
            ->toHaveKey('inventory.stock', 50.00)
            ->toHaveKey('inventory.inventory_units.0.quantity', 40.00)
            ->toHaveKey('inventory.inventory_units.0.batch.number', '123456')
            ->toHaveKey('inventory.inventory_units.1.quantity', 10.00)
            ->toHaveKey('merge_product_transactions.0.old_product.upc', $deletedProduct->upc)
            ->toHaveKey('inventory.inventory_units.1.batch.number', '123456')
            ->toHaveKey('boxes');
    }
);

test(
    'getByIdsWithBrandAndCategories method returns the products list',
    function (): void {
        $response = $this->productQueries->getByIdsWithBrandAndCategories([$this->productA->id], $this->companyA->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $this->productA->id)
            ->toHaveKey('name', $this->productA->name)
            ->toHaveKey('upc', $this->productA->upc)
            ->toHaveKey('vendor_id', $this->productA->vendor_id)
            ->toHaveKeys(['brand', 'categories', 'tags', 'tiers', 'unit_of_measure', 'boxes', 'vendor']);
    }
);

test('doAllProductsExist method works as expected', function (): void {
    $response = $this->productQueries->doAllProductsExist($this->companyA->id, [$this->productA->id]);
    expect($response)->toBeTrue();
});

test('doAllActiveProductsExist checks for products existence as expected', function (): void {
    $response = $this->productQueries->doAllActiveProductsExist($this->companyA->id, [$this->productA->id]);

    expect($response)->toBeTrue();

    $response = $this->productQueries->doAllActiveProductsExist($this->companyA->id, [0]);

    expect($response)->toBeFalse();
});

test(
    'getActiveFilteredProducts method returns collection of products that match with the search text product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'DEF',
            'code' => '3456',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $response = $this->productQueries->getActiveFilteredProducts([
            'search_text' => 'DEF',
            'number_of_records' => 5,
        ], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('name', $product->compound_product_name);
    }
)->with([[true], [false]]);

test(
    'getSelectedActiveProductsForBarcodePrint method returns collection of products by ids for barcode prints and product variant false',
    function (): void {
        Config::set('app.product_variant', false);
        $response = $this->productQueries->getSelectedActiveProductsForBarcodePrint(
            [$this->productA->id],
            $this->companyA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->productA->id)
            ->toHaveKeys(
                ['name', 'upc', 'retail_price', 'brand_id', 'article_number', 'color_id', 'size_id', 'style_id']
            );
    }
);

test(
    'getSelectedActiveProductsForBarcodePrint method returns collection of products by ids for barcode prints and product variant true',
    function (): void {
        Config::set('app.product_variant', true);

        $response = $this->productQueries->getSelectedActiveProductsForBarcodePrint(
            [$this->productA->id],
            $this->companyA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->productA->id)
            ->toHaveKeys(['name', 'upc', 'retail_price', 'master_product_id']);
    }
);

test(
    'getActiveProductsFilteredByNameBrandAndCategory method returns the list of products filter by brand and category product variant',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'DEF',
            'code' => '3456',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
        ]);

        if ($productVariant) {
            $masterProduct->categories()
                ->attach($category->id, [
                    'sort_order' => 0,
                ]);

            $product->masterProduct = $masterProduct;
        } else {
            $product->categories()
                ->attach($category->id, [
                    'sort_order' => 0,
                ]);
        }

        $response = $this->productQueries->getActiveProductsFilteredByNameBrandAndCategory([
            'search_text' => 'DEF',
            'brand_id' => $productVariant ? $masterProduct->brand_id : $product->brand_id,
            'category_id' => $category->id,
        ], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('name', $product->name)
            ->toHaveKey('brand_id', $product->brand_id);

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKeys(['master_product.brand', 'master_product.categories']);
        } else {
            expect($response->first()->toArray())
                ->toHaveKeys(['brand', 'categories']);
        }
    }
)->with([[true], [false]]);

test('getActiveProductWithBasicColumnsById method returns the specified product', function (): void {
    $response = $this->productQueries->getActiveProductWithBasicColumnsById($this->productA->id, $this->companyA->id);

    expect($response)
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('name', $this->productA->compound_product_name);
});

test(
    'getActiveInventoryProductsByIds method returns the list of products when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $response = $this->productQueries->getActiveInventoryProductsByIds([$this->productA->id], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->productA->id)
            ->toHaveKey('compound_product_name', $this->productA->compound_product_name)
            ->toHaveKey('has_batch', $this->productA->has_batch)
            ->toHaveKey('unit_of_measure_id', $this->productA->unit_of_measure_id)
            ->toHaveKey('upc', $this->productA->upc);
    }
);

test(
    'getActiveInventoryProductsByIds method returns the list of products when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'ABCD131333',
            'code' => 'A12342426',
            'upc' => 'UPC12124124',
            'article_number' => '12346644',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'is_non_selling_item' => false,
            'is_available_in_pos' => true,
            'is_available_in_ecommerce' => false,
            'master_product_id' => $masterProduct->id,
        ]);

        $response = $this->productQueries->getActiveInventoryProductsByIds([$product->id], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('compound_product_name', $product->compound_product_name)
            ->toHaveKey('master_product.has_batch', $product->masterProduct->has_batch)
            ->toHaveKey('master_product.unit_of_measure_id', $product->masterProduct->unit_of_measure_id)
            ->toHaveKey('upc', $product->upc);
    }
);

test(
    'getProductsWithArchivedByIds method returns the list of products when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'is_non_inventory' => false,
            'status' => Statuses::ARCHIVED->value,
        ]);

        $response = $this->productQueries->getProductsWithArchivedByIds([$product->id], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('compound_product_name', $product->compound_product_name)
            ->toHaveKey('has_batch', $product->has_batch)
            ->toHaveKey('upc', $product->upc)
            ->toHaveKey('status', Statuses::ARCHIVED->value);
    }
);

test(
    'getProductsWithArchivedByIds method returns the list of products when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'is_non_inventory' => false,
            'status' => Statuses::ARCHIVED->value,
            'master_product_id' => $masterProduct->id,
        ]);

        $response = $this->productQueries->getProductsWithArchivedByIds([$product->id], $this->companyA->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('compound_product_name', $product->compound_product_name)
            ->toHaveKey('master_product.has_batch', $masterProduct->has_batch)
            ->toHaveKey('upc', $product->upc)
            ->toHaveKey('status', Statuses::ARCHIVED->value);
    }
);

test('markAsArchived method sets the product as archive', function (): void {
    Queue::fake();
    $this->productQueries->markAsArchived($this->productA->id, $this->companyA->id);

    $this->assertDatabaseHas('products', [
        'id' => $this->productA->id,
        'status' => Statuses::ARCHIVED->value,
    ]);
});

test('restore method sets the product as unarchive', function (): void {
    Queue::fake();
    $this->productQueries->restore($this->productB->id, $this->companyA->id);

    $this->assertDatabaseHas('products', [
        'id' => $this->productB->id,
        'status' => Statuses::ACTIVE->value,
    ]);
});

test(
    'getProductsWithRelationsForExport method returns the product data with relations for export',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'ABCD',
            'code' => '3456',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $response = $this->productQueries->getProductsWithRelationsForExport([
            'search_text' => $product->compound_product_name,
            'sort_by' => null,
            'sort_direction' => null,
            'status' => ProductStatuses::ACTIVE->value,
            'batch' => ProductBatches::ALL->value,
            'date_range' => null,
            'product_type_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'color_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'product_collection_ids' => null,
            'attributes' => [],
        ], $this->companyA->id);

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKey('name', $product->name)
                ->toHaveKey('code', $product->code)
                ->toHaveKey('master_product_id', $masterProduct->id);
        } else {
            expect($response->first()->toArray())
                ->toHaveKey('name', $product->name)
                ->toHaveKey('code', $product->code);
        }
    }
)->with([[true], [false]]);

test('getActiveProductIds method returns the product ids', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'XYZ',
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $inventoryUpdate = InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id,
    ]);

    $response = $this->productQueries->getActiveProductIds($this->companyA->id, $inventoryUpdate->location_id);

    expect($response->toArray())->toBeArray();
});

test('getStockByStoreIdProductIdsAndDate method returns product inventory updates list as expected', function (): void {
    $companyId = Company::factory()->create()->id;

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'status' => Statuses::ACTIVE->value,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
        'name' => 'ABCD',
        'code' => 'XYZW',
    ]);

    $sale = Sale::factory()->create();
    $currentDate = Carbon::now()->format('Y-m-d');

    InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'affected_by_type' => ModelMapping::SALE->name,
        'affected_by_id' => $sale->id,
        'happened_at' => $currentDate,
    ]);

    $response = $this->productQueries->getStockByStoreIdProductIdsAndDate(
        $location->id,
        [$product->id],
        $currentDate,
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $product->id)
        ->toHaveKey('latest_inventory_update');
});

test(
    'updateProductPrice method updates the product retail price according to UPC',
    function (): void {
        Queue::fake();
        $this->productQueries->updateProductPrice([
            'retail_price' => 100,
            'franchise_price_1' => 100,
            'franchise_price_3' => 100,
            'franchise_price_2' => 100,
            'wholesale_price' => 100,
            'company_or_tender_price' => 100,
            'branch_price' => 100,
            'minimum_price' => 100,
            'original_capital_price' => 100,
            'capital_price' => 100,
            'staff_price' => 100,
        ], $this->productA->upc, $this->companyA->id);

        $this->assertDatabaseHas('products', [
            'id' => $this->productA->id,
            'retail_price' => 100,
        ]);
    }
);

test('updateByUpc method updates the product according to UPC', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $color = Color::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $size = Size::factory()->create([
        'company_id' => $this->companyA->id,
    ]);
    $newProductRecord = Product::factory()->make([
        'color_id' => $color->id,
        'size_id' => $size->id,
        'upc' => $this->productA->upc,
    ])->toArray();
    unset($newProductRecord['company_id']);
    $newProductRecord['category_ids'] = [];
    $newProductRecord['tag_ids'] = [];
    $newProductRecord['sale_channel_ids'] = [];
    $newProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;

    $this->productQueries->updateByUpc($newProductRecord, $this->productA->upc, $this->companyA->id);

    unset($newProductRecord['category_ids'], $newProductRecord['compound_product_name'], $newProductRecord['tag_ids'], $newProductRecord['sale_channel_ids']);

    $this->assertDatabaseHas('products', $newProductRecord);
});

test('searchByArticleNumber method returns the list of products', function (): void {
    $response = $this->productQueries->searchByArticleNumber($this->productA->article_number, $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('compound_product_name', $this->productA->compound_product_name)
        ->toHaveKey('size_id', $this->productA->size_id)
        ->toHaveKey('color_id', $this->productA->color_id)
        ->toHaveKey('has_batch', $this->productA->has_batch)
        ->toHaveKey('upc', $this->productA->upc)
        ->toHaveKey('name', $this->productA->name);
});

test('searchActiveInventoryProductsByArticleNumber method returns the list of products', function ($value): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'is_non_inventory' => $value,
        'status' => Statuses::ACTIVE->value,
    ]);

    $response = $this->productQueries->searchActiveInventoryProductsByArticleNumber(
        $product->article_number,
        $this->companyA->id
    );

    if (false === $value) {
        expect($response->first()->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKeys(['compound_product_name', 'has_batch', 'size', 'color', 'upc', 'unit_of_measure']);
    }

    if (true === $value) {
        expect($response)
            ->toBeEmpty();
    }
})->with([true, false]);

test(
    'getProductsReportForExport method returns the list of products for export',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $filterData = [
            'search_text' => '',
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'product_id' => null,
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'location_ids' => [],
            'article_numbers' => null,
            'date_range' => [],
            'tag_ids' => null,
            'region_ids' => null,
            'counter_ids' => null,
            'product_collection_id' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        if ($productVariant) {
            $this->productA->master_product_id = $masterProduct->id;
            $this->productA->save();
            $this->productA->masterProduct = $masterProduct;
        }

        $saleItems = SaleItem::factory(2)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'sale_return_item_id' => null,
            'quantity' => 20,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $sale->counter_update_id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->getKey(),
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getProductsReportForExport($filterData, $this->companyA->id);

        expect($response->first())
            ->toHaveKey('name', $this->productA->name)
            ->toHaveKey('sum_sale_return_quantity', 10)
            ->toHaveKey('sum_sale_quantity', CommonFunctions::numberFormat($saleItems->sum('quantity')));
    }
)->with([[true], [false]]);

test('getPaginatedProductsReport method returns the list of products', function (bool $productVariant): void {
    Config::set('app.product_variant', $productVariant);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'product_id' => null,
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => null,
        'date_range' => [],
        'tag_ids' => null,
        'region_ids' => null,
        'counter_ids' => null,
        'product_collection_id' => null,
    ];
    $sale = Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE,
    ]);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $this->productA->master_product_id = $masterProduct->id;
        $this->productA->save();
        $this->productA->masterProduct = $masterProduct;
    }

    $saleItems = SaleItem::factory(3)->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $sale->counter_update_id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->getKey(),
        'product_id' => $this->productA->id,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getPaginatedProductsReport($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

    expect($response->first())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('sum_sale_return_quantity', 10)
        ->toHaveKey('sum_sale_quantity', CommonFunctions::numberFormat($saleItems->sum('quantity')));
})->with([[true], [false]]);

test('existsByUpc method returns result as expected', function (): void {
    $response = $this->productQueries->existsByUpc($this->productB->upc, $this->companyA->id);
    $this->assertTrue($response);

    $response = $this->productQueries->existsByUpc('ABCDEFGH', $this->companyA->id);
    $this->assertFalse($response);
});

test('getProductsForApplication method returns paginated results as expected', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD1',
        'code' => 'A12367',
        'upc' => '1234',
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    Inventory::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'stock' => 0,
    ]);

    $response = $this->productQueries->getProductsForApplication([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'stock_product' => 'all_stock',
        'location_id' => $location->id,
    ], $this->companyA->id);

    expect($response->toArray()['data'][0])
        ->toHaveKey('name', $product->name)
        ->toHaveKey('retail_price', $product->retail_price);
});

test('getProductDetailsForApplication method returns details as expected', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD1',
        'code' => 'A12367',
        'upc' => '1234',
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
    ]);

    $response = $this->productQueries->getProductDetailsForApplication($product->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKey('retail_price', $product->retail_price);
});

test('if the product is merged then the product is soft deleted', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD1',
        'code' => 'A12367',
        'upc' => '1234',
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
    ]);

    $this->productQueries->deleteProduct($this->companyA->id, $product->id);

    $this->assertSoftDeleted($product);
});

test('draft product is soft deleted', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD1',
        'code' => 'A12367',
        'upc' => '1234',
        'article_number' => '12345',
        'status' => Statuses::DRAFT->value,
        'is_non_inventory' => false,
    ]);

    $this->productQueries->deleteDraftProducts($this->companyA->id, [$product->id]);

    $this->assertSoftDeleted($product);
});

test('it checks the product is active or archived', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD1',
        'code' => 'A12367',
        'upc' => '1234',
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
    ]);

    $responseA = $this->productQueries->checkProductIsActive($this->companyA->id, $product->id);

    $this->assertEquals(Statuses::ACTIVE->value, $responseA);

    $product->status = Statuses::ARCHIVED->value;
    $product->save();

    $responseB = $this->productQueries->checkProductIsActive($this->companyA->id, $product->id);

    expect($responseB)->toBeNull();
});

test(
    'getCachedProductQuantitySoldReportWithArticleNumber method returns the list of products',
    function (bool $withArticleNumber): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => null,
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => $withArticleNumber ? [$this->productA->article_number] : null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItemsProductA = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleItemsProductB = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productB->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedProductQuantitySoldReportWithArticleNumber(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        expect($response->first())
            ->toHaveKey('name', $withArticleNumber ? $this->productA->name : $this->productB->name)
            ->toHaveKey('total_quantity_returned', $withArticleNumber ? 10 : null)
            ->toHaveKey(
                'total_quantity_sold',
                $withArticleNumber ? CommonFunctions::numberFormat(
                    (float) $saleItemsProductA->sum('quantity')
                ) : CommonFunctions::numberFormat((float) $saleItemsProductB->quantity)
            );
    }
)->with([false, true]);

test(
    'getCachedSingleProductQuantitySoldReportWithArticleNumber method returns the list of products with individual sorting',
    function (bool $withArticleNumber): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'separate_column_sorting' => true,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => null,
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => $withArticleNumber ? [$this->productA->article_number] : null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItemsProductA = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleItemsProductB = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productB->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedSingleProductQuantitySoldReportWithArticleNumber(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        expect($response->first())
            ->toHaveKey('name', $withArticleNumber ? $this->productA->name : $this->productB->name)
            ->toHaveKey('total_quantity_returned', $withArticleNumber ? 10 : null)
            ->toHaveKey(
                'total_quantity_sold',
                $withArticleNumber ? CommonFunctions::numberFormat(
                    (float) $saleItemsProductA->sum('quantity')
                ) : CommonFunctions::numberFormat((float) $saleItemsProductB->quantity)
            );
    }
)->with([false, true]);

test(
    'getCachedSingleCompareProductQuantitySoldReportWithArticleNumber method returns the list of products with individual sorting',
    function (bool $withArticleNumber): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'compare_sort_by' => null,
            'compare_sort_direction' => null,
            'separate_column_sorting' => true,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => null,
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => $withArticleNumber ? [$this->productA->article_number] : null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItemsProductA = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleItemsProductB = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productB->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedSingleCompareProductQuantitySoldReportWithArticleNumber(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        expect($response->first())
            ->toHaveKey('name', $withArticleNumber ? $this->productA->name : $this->productB->name)
            ->toHaveKey('compare_total_quantity_returned', $withArticleNumber ? 10 : null)
            ->toHaveKey(
                'compare_total_quantity_sold',
                $withArticleNumber ? CommonFunctions::numberFormat(
                    (float) $saleItemsProductA->sum('quantity')
                ) : CommonFunctions::numberFormat((float) $saleItemsProductB->quantity)
            );
    }
)->with([false, true]);

test(
    'getCachedProductQuantitySoldReportWithUpc method returns the list of products',
    function (bool $withArticleNumber): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => [],
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => $withArticleNumber ? [$this->productA->article_number] : null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItemsProductA = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleItemsProductB = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productB->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedProductQuantitySoldReportWithUpc($filterData, $this->companyA->id);

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        expect($response->first())
        ->toHaveKey('name', $withArticleNumber ? $this->productA->name : $this->productB->name)
        ->toHaveKey('total_quantity_returned', $withArticleNumber ? 10 : null)
        ->toHaveKey(
            'total_quantity_sold',
            $withArticleNumber ? CommonFunctions::numberFormat(
                (float) $saleItemsProductA->sum('quantity')
            ) : CommonFunctions::numberFormat((float) $saleItemsProductB->quantity)
        );
    }
)->with([false, true]);

test(
    'getCachedSingleProductQuantitySoldReportWithUpc method returns the list of products',
    function (bool $withArticleNumber): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'compare_sort_by' => null,
            'compare_sort_direction' => null,
            'separate_column_sorting' => true,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => [],
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => $withArticleNumber ? [$this->productA->article_number] : null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItemsProductA = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleItemsProductB = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productB->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedSingleProductQuantitySoldReportWithUpc(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        expect($response->first())
            ->toHaveKey('name', $withArticleNumber ? $this->productA->name : $this->productB->name)
            ->toHaveKey('total_quantity_returned', $withArticleNumber ? 10 : null)
            ->toHaveKey(
                'total_quantity_sold',
                $withArticleNumber ? CommonFunctions::numberFormat(
                    (float) $saleItemsProductA->sum('quantity')
                ) : CommonFunctions::numberFormat((float) $saleItemsProductB->quantity)
            );
    }
)->with([false, true]);

test(
    'getCachedSingleCompareProductQuantitySoldReportWithUpc method returns the list of products',
    function (bool $withArticleNumber): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'compare_sort_by' => null,
            'compare_sort_direction' => null,
            'separate_column_sorting' => true,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => [],
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => $withArticleNumber ? [$this->productA->article_number] : null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItemsProductA = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleItemsProductB = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productB->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedSingleCompareProductQuantitySoldReportWithUpc(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        expect($response->first())
            ->toHaveKey('name', $withArticleNumber ? $this->productA->name : $this->productB->name)
            ->toHaveKey('compare_total_quantity_returned', $withArticleNumber ? 10 : null)
            ->toHaveKey(
                'compare_total_quantity_sold',
                $withArticleNumber ? CommonFunctions::numberFormat(
                    (float) $saleItemsProductA->sum('quantity')
                ) : CommonFunctions::numberFormat((float) $saleItemsProductB->quantity)
            );
    }
)->with([false, true]);

test(
    'getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber method returns the list of products',
    function (): void {
        $date = now();
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'date_range' => [$date->format('Y-m-d'), $date->format('Y-m-d')],
            'tag_ids' => null,
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $saleItems = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        $saleReturnId = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ])->id;

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturnId,
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first())
            ->toHaveKey('total_quantity_returned', 10)
            ->toHaveKey('total_quantity_sold', CommonFunctions::numberFormat($saleItems->sum('quantity')));
    }
);

test(
    'getProductsByUpcForInterCompany method returns collection when product variant',
    function (bool $productVariant): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'DEFHIJ',
            'code' => '3456',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'has_batch' => false,
        ]);

        $response = $this->productQueries->getProductsByUpcForInterCompany([$product->upc], $this->companyA->id);

        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => true,
            ]);

            $product->master_product_id = $masterProduct->id;
            $product->save();

            $product->masterProduct = $masterProduct;
        }

        expect($response->first()->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKey('upc', $product->upc)
            ->toHaveKey('has_batch', $productVariant ? $masterProduct->has_batch : $product->has_batch);
    }
)->with([[true], [false]]);

test(
    'getCachedConsolidateProductQuantitySoldSumAndCountWithUpc method returns the list of products',
    function (): void {
        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'date_range' => [],
            'tag_ids' => [],
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'article_numbers' => null,
            'color_ids' => null,
            'style_ids' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        $saleItems = SaleItem::factory(3)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'sale_return_item_id' => null,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedConsolidateProductQuantitySoldSumAndCountWithUpc(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first())
            ->toHaveKey('total_quantity_returned', 10)
            ->toHaveKey('total_quantity_sold', CommonFunctions::numberFormat($saleItems->sum('quantity')));
    }
);

test('it returns the sales summary for a product within a specific category and date', function (): void {
    $categoryId = Category::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'test',
    ])->id;

    $this->productA->categories()->attach([$categoryId]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $categoryId,
        'type' => StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $this->productA->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a product within a specific color and date', function (): void {
    $colorId = Color::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $colorId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $colorId,
        'type' => StoreRevenueDashboardTableFilterTypes::COLORS->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a product within a specific brand and date', function (): void {
    $brandId = Brand::factory()->create([
        'name' => 'test',
    ])->id;

    $this->companyA->brands()->attach($brandId);

    $product = Product::factory()->create([
        'brand_id' => $brandId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $brandId,
        'type' => StoreRevenueDashboardTableFilterTypes::BRANDS->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a product within a specific department and date', function (): void {
    $departmentId = Department::factory()->create([
        'name' => 'test',
        'company_id' => $this->companyA->id,
    ])->id;

    $product = Product::factory()->create([
        'department_id' => $departmentId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $departmentId,
        'type' => StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a product within a specific color group and date', function (): void {
    $colorGroupId = ColorGroup::factory()->create([
        'name' => 'test',
        'company_id' => $this->companyA->id,
    ])->id;

    $colorId = Color::factory()->create([
        'name' => 'test',
        'company_id' => $this->companyA->id,
        'group_id' => $colorGroupId,
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $colorId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $colorGroupId,
        'type' => StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a product within a specific size and date', function (): void {
    $sizeId = Size::factory()->create([
        'name' => 'test',
        'company_id' => $this->companyA->id,
    ])->id;

    $product = Product::factory()->create([
        'size_id' => $sizeId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $sizeId,
        'type' => StoreRevenueDashboardTableFilterTypes::SIZES->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a product within a specific style and date', function (): void {
    $styleId = Style::factory()->create([
        'name' => 'test',
        'company_id' => $this->companyA->id,
    ])->id;

    $product = Product::factory()->create([
        'style_id' => $styleId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'id' => $styleId,
        'type' => StoreRevenueDashboardTableFilterTypes::STYLES->value,
        'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getProductSalesSummary($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sell through for a product within a specific color and date range', function (): void {
    $date = Carbon::now();

    $colorId = Color::factory()->create([
        'name' => 'Blue',
        'company_id' => $this->companyA->id,
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $colorId,
        'company_id' => $this->companyA->id,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $filterData = [
        'location_id' => $locationId,
        'report_type' => null,
        'product_id' => null,
        'category_id' => null,
        'brand_id' => null,
        'size_id' => null,
        'color_id' => null,
        'date' => null,
        'department_ids' => [],
        'article_numbers' => [],
        'tag_ids' => [],
        'include_by' => [],
        'style_ids' => [],
        'date_range' => [$date->format('Y-m-d'), $date->format('Y-m-d')],
        'filter_by' => SellThroughFilterTypes::ALL->value,
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $goodsReceivedNoteId = GoodsReceivedNote::factory()->create([
        'company_id' => $this->companyA->id,
        'location_id' => $locationId,
    ])->id;

    $inventoryUpdate = InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $locationId,
        'affected_by_id' => $goodsReceivedNoteId,
        'affected_by_type' => ModelMapping::GOODS_RECEIVED_NOTE->name,
        'happened_at' => $date->format('Y-m-d H:i:s'),
        'quantity' => 10,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getCachedSellThroughForSummarySaleThroughReportWithStore(
        $filterData,
        $this->companyA->id
    );

    expect($response)->toBeInstanceOf(Collection::class);
});

test('it returns the sell through for a product within a specific color and date', function (): void {
    $date = Carbon::now();

    $colorId = Color::factory()->create([
        'name' => 'Blue',
        'company_id' => $this->companyA->id,
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $colorId,
        'company_id' => $this->companyA->id,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $filterData = [
        'location_id' => $locationId,
        'report_type' => null,
        'product_id' => null,
        'category_id' => null,
        'brand_id' => null,
        'size_id' => null,
        'color_id' => null,
        'department_ids' => [],
        'article_numbers' => [],
        'include_by' => [],
        'filter_by' => 1,
        'tag_ids' => [],
        'style_ids' => [],
        'date' => $date->format('Y-m-d'),
        'date_range' => null,
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $date->format('Y-m-d H:i:s'),
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $goodsReceivedNoteId = GoodsReceivedNote::factory()->create([
        'company_id' => $this->companyA->id,
        'location_id' => $locationId,
    ])->id;

    InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $locationId,
        'affected_by_id' => $goodsReceivedNoteId,
        'affected_by_type' => ModelMapping::GOODS_RECEIVED_NOTE->name,
        'happened_at' => $date->format('Y-m-d H:i:s'),
        'quantity' => 10,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getCachedSellThroughForSummarySaleThroughReportWithStore(
        $filterData,
        $this->companyA->id
    );

    expect($response)->toBeInstanceOf(Collection::class);
});

test('it returns the sales grade analysis for a product', function (): void {
    $date = Carbon::now();

    $colorId = Color::factory()->create([
        'name' => 'Blue',
        'company_id' => $this->companyA->id,
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $colorId,
        'company_id' => $this->companyA->id,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $filterData = [
        'location_id' => $locationId,
        'date' => $date->format('Y-m-d'),
        'search_text' => '',
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 10,
        'article_numbers' => [],
        'category_ids' => [],
        'brand_ids' => [],
        'color_ids' => [],
        'size_ids' => [],
        'style_ids' => [],
        'tag_ids' => [],
        'department_ids' => [],
        'product_id' => null,
        'grade_filter' => null,
    ];

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItems = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'total_price_paid' => 200,
        'sale_return_item_id' => null,
    ]);

    $goodsReceivedNoteId = GoodsReceivedNote::factory()->create([
        'company_id' => $this->companyA->id,
        'location_id' => $locationId,
    ])->id;

    InventoryUpdate::factory()->create([
        'product_id' => $product->id,
        'location_id' => $locationId,
        'affected_by_id' => $goodsReceivedNoteId,
        'affected_by_type' => ModelMapping::GOODS_RECEIVED_NOTE->name,
        'happened_at' => $date->format('Y-m-d H:i:s'),
        'quantity' => 10,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'original_sale_id' => $sale->id,
    ]);

    SaleReturnItem::factory()->create([
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItems->id,
        'product_id' => $product->id,
        'total_price_paid' => 100,
        'quantity' => 10,
    ]);

    [$cacheKey, $cacheExpirationTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
        $filterData,
        'getCachedSaleThroughAnalysisData',
        $this->companyA->id
    );

    Cache::forget($cacheKey);
    $response = $this->productQueries->getCachedSaleThroughAnalysisData($filterData, $this->companyA->id);
    $this->assertTrue(Cache::has($cacheKey));

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['article_number', 'sold', 'returned', 'total_sales', 'total_units_sold']);

    $secondResponse = $this->productQueries->getCachedSaleThroughAnalysisData($filterData, $this->companyA->id);

    expect($secondResponse)->toBeInstanceOf(Collection::class);

    expect($secondResponse->first()->toArray())
        ->toHaveKey('name', $product->name)
        ->toHaveKeys(['article_number', 'sold', 'returned', 'total_sales', 'total_units_sold']);
});

test('getProductsAgeingReportForExport method returns the list of products for export', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory(2)->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
        'quantity' => 20,
    ]);

    SaleReturnItem::factory()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
    ]);

    $filterData = [
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'product_id' => $this->productA->id,
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [$locationId],
        'article_numbers' => null,
        'date_range' => [],
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'age_category_id' => null,
        'date' => null,
    ];

    $response = $this->productQueries->getProductsAgeingReportForExport($filterData, $this->companyA->id);

    expect($response->first())
        ->toHaveKey('name', $this->productA->name);
});

test('getPaginatedProductsAgeingReport method returns the list of products', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory(3)->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'sale_return_item_id' => null,
    ]);

    SaleReturnItem::factory()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'product_id' => $this->productA->id,
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [$locationId],
        'article_numbers' => null,
        'date_range' => [],
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'age_category_id' => null,
        'last_selling_date_range' => [],
    ];

    $response = $this->productQueries->getPaginatedProductsAgeingReport($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

    expect($response->first())
        ->toHaveKey('name', $this->productA->name);
});

test(
    'getProductsAgeingReportByMonthAndYearForExport method returns the list of products for export',
    function (): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        SaleItem::factory(2)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'sale_return_item_id' => null,
            'quantity' => 20,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $filterData = [
            'search_text' => '',
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'product_id' => null,
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'location_ids' => [$locationId],
            'article_numbers' => ['1234'],
            'tag_ids' => null,
            'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
            'age_category_id' => null,
            'last_selling_date_range' => [],
        ];

        $response = $this->productQueries->getProductsAgeingReportByMonthAndYearForExport(
            $filterData,
            $this->companyA->id
        );

        expect($response->first())
        ->toHaveKey('name', $this->productA->name);
    }
);

test('getPaginatedProductsAgeingReportByMonthAndYear method returns the list of products', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyA->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    SaleItem::factory(3)->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'sale_return_item_id' => null,
    ]);

    SaleReturnItem::factory()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
    ]);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'product_id' => null,
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [$locationId],
        'article_numbers' => ['1234'],
        'tag_ids' => null,
        'age_of_product_type' => AgeOfProductTypes::CREATED_AT->value,
        'age_category_id' => null,
        'last_selling_date_range' => [],
    ];

    $response = $this->productQueries->getPaginatedProductsAgeingReportByMonthAndYear($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

    expect($response->first())
        ->toHaveKey('name', $this->productA->name);
});

test(
    'getCachedSellThroughSalesAndReturnsDataByProductArticleNumberForPaginate method returns the sold and returns of sales article number',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'location_id' => null,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'date_range' => null,
            'per_page' => null,
            'sort_by' => null,
            'search_text' => null,
            'include_by' => [],
            'filter_by' => 1,
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedSellThroughSalesAndReturnsDataByProductArticleNumberForPaginate(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test(
    'getCachedSellThroughSalesAndReturnsDataByProductArticleNumberForConsolidateData method returns the sold and returns of sales article numbers',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'location_id' => null,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'date_range' => null,
            'per_page' => null,
            'sort_by' => null,
            'search_text' => null,
            'include_by' => [],
            'filter_by' => 1,
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getCachedSellThroughSalesAndReturnsDataByProductArticleNumberForConsolidateData(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test('A product can be fetched by id and company id', function (): void {
    $response = $this->productQueries->getByIdForDashboardFilter($this->productA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->productA->compound_product_name)
        ->toHaveKey('id', $this->productA->id);
});

test(
    'getActiveInventoryProductsFilteredByNameBrandAndCategory method return inventory products with product variant ',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $this->productA->is_non_inventory = true;
        $this->productA->save();

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => true,
            ]);
        }

        $productOne = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'DEF',
            'code' => '3456',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'is_non_inventory' => false,
        ]);

        $productTwo = Product::factory()->create([
            'name' => 'product 2',
            'company_id' => $this->companyA->id,
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $productOne->masterProduct = $masterProduct;
            $productTwo->masterProduct = $masterProduct;
        }

        $location = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $filterData = [
            'search_text' => null,
            'category_id' => null,
            'brand_id' => null,
            'has_inventory' => true,
            'location_id' => $location->id,
        ];

        Inventory::factory()->create([
            'product_id' => $productOne->id,
            'location_id' => $location->id,
            'stock' => 1,
            'reserved_stock' => 0,
        ]);

        $response = $this->productQueries->getActiveInventoryProductsFilteredByNameBrandAndCategory(
            $filterData,
            $this->companyA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('name', $productOne->name)
            ->toHaveKey('id', $productOne->id);

        expect($response->first()->toArray())
            ->not
            ->toHaveKey('name', $productTwo->name);
    }
)->with([[true], [false]]);

test('A product can be fetched with retail_price', function (): void {
    $response = $this->productQueries->getRetailPriceByIds($this->companyA->id, [$this->productA->id]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('retail_price', $this->productA->retail_price);
});

test('getIdByUpcForLoyaltyPoint method returns product details', function (): void {
    $response = $this->productQueries->getIdByUpcForLoyaltyPoint($this->productA->upc, $this->companyA->id);
    $this->assertEquals($this->productA->id, $response);
});

test(
    'getActiveFilteredRegularProducts method returns collection of products that match with the search text',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
                'is_non_selling_item' => false,
                'status' => Statuses::ACTIVE->value,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'ABCDEF',
            'code' => 'A123906',
            'upc' => 'A123',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        } else {
            $product->is_non_inventory = false;
            $product->is_non_selling_item = false;
            $product->type_id = ProductTypes::REGULAR_PRODUCT->value;
            $product->save();
        }

        $response = $this->productQueries->getActiveFilteredRegularProducts([
            'search_text' => 'ABCDEF',
            'number_of_records' => 5,
        ], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('name', $product->compound_product_name);
    }
)->with([[true], [false]]);

test(
    'getActiveRegularProductsFilteredByNameBrandAndCategory method returns the list of products filter by brand and category',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
                'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'compound_product_name' => 'TEST1',
            'code' => 'A123678',
            'upc' => 'A345',
            'article_number' => '1234',
            'status' => Statuses::ACTIVE->value,
            'is_non_inventory' => false,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'is_non_selling_item' => false,
        ]);

        if ($productVariant) {
            $masterProduct->categories()
                ->attach($category->id, [
                    'sort_order' => 0,
                ]);

            $product->masterProduct = $masterProduct;
        } else {
            $product->type_id = ProductTypes::REGULAR_PRODUCT->value;
            $product->save();

            $product->categories()
                ->attach($category->id, [
                    'sort_order' => 0,
                ]);
        }

        $response = $this->productQueries->getActiveRegularProductsFilteredByNameBrandAndCategory([
            'search_text' => 'TEST1',
            'brand_id' => $productVariant ? $product->masterProduct->brand_id : $product->brand_id,
            'category_id' => $category->id,
        ], $this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('name', $product->name);

        if ($productVariant) {
            expect($response->first()->toArray())
                ->toHaveKeys(['master_product.brand', 'master_product.categories']);
        } else {
            expect($response->first()->toArray())
                ->toHaveKeys(['brand', 'categories']);
        }
    }
)->with([[true], [false]]);

test('getIdByUpc method returns product details', function (): void {
    $response = $this->productQueries->getIdByUpc($this->productA->upc, $this->productA->company_id);
    $this->assertEquals($this->productA->id, $response);
});

test('getProductTypeAndArticleNumber method returns product by id & company id.', function (): void {
    $response = $this->productQueries->getProductTypeAndArticleNumber($this->productA->id, $this->productA->company_id);

    expect($response->toArray())
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('type_id', $this->productA->type_id)
        ->toHaveKey('article_number', $this->productA->article_number);
});

test('existsByIdAndCompanyId method returns result as expected', function (): void {
    $response = $this->productQueries->existsByIdAndCompanyId($this->productA->id, $this->companyA->id);
    $this->assertTrue($response);

    $response = $this->productQueries->existsByIdAndCompanyId(200, $this->companyA->id);
    $this->assertFalse($response);
});

test(
    'getProfitsAndLossesReportForExport method returns the list of products for export',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'product_id' => null,
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'location_ids' => [],
            'article_numbers' => [],
            'date_range' => [],
            'tag_ids' => null,
            'region_ids' => null,
            'counter_ids' => null,
            'product_collection_id' => null,
        ];

        $sale = Sale::factory()->create([
            'status' => SaleStatus::REGULAR_SALE,
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
                'is_non_selling_item' => false,
            ]);

            $this->productA->master_product_id = $masterProduct->id;
            $this->productA->save();
            $this->productA->masterProduct = $masterProduct;
        }

        $saleItems = SaleItem::factory(2)->create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'is_exchange' => 0,
            'sale_return_item_id' => null,
            'quantity' => 20,
        ]);

        SaleReturnItem::factory()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->getProfitsAndLossesReportForExport($filterData, $this->companyA->id);

        expect($response->first())
            ->toHaveKey('total_quantity_returned', 10)
            ->toHaveKey('total_quantity_sold', CommonFunctions::numberFormat($saleItems->sum('quantity')))
            ->toHaveKey(
                'total_purchase_cost',
                CommonFunctions::numberFormat($this->productA->purchase_cost * $saleItems->sum('quantity'))
            );
    }
)->with([[true], [false]]);

test('getPaginatedProfitsAndLossesReport method returns the list of products', function ($productVariant): void {
    Config::set('app.product_variant', $productVariant);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'product_id' => null,
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'article_numbers' => [],
        'date_range' => [],
        'tag_ids' => null,
        'region_ids' => null,
        'counter_ids' => null,
        'product_collection_id' => null,
    ];
    $sale = Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE,
    ]);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
            'is_non_selling_item' => false,
        ]);

        $this->productA->master_product_id = $masterProduct->id;
        $this->productA->save();
        $this->productA->masterProduct = $masterProduct;
    }

    $saleItems = SaleItem::factory(3)->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'quantity' => 20,
        'sale_return_item_id' => null,
    ]);

    SaleReturnItem::factory()->create([
        'product_id' => $this->productA->id,
        'quantity' => 10,
    ]);

    $response = $this->productQueries->getPaginatedProfitsAndLossesReport($filterData, $this->companyA->id);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

    expect($response->first())
        ->toHaveKey('total_quantity_returned', 10)
        ->toHaveKey('total_quantity_sold', CommonFunctions::numberFormat($saleItems->sum('quantity')))
        ->toHaveKey(
            'total_purchase_cost',
            CommonFunctions::numberFormat($this->productA->purchase_cost * $saleItems->sum('quantity'))
        );
})->with([[true], [false]]);

test('getFilteredTotalsForProfitsAndLossesReport method returns the list of products', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'product_id' => null,
        'category_ids' => [],
        'brand_ids' => [],
        'department_ids' => [],
        'size_ids' => [],
        'color_ids' => [],
        'location_ids' => [],
        'tag_ids' => [],
        'article_numbers' => [],
        'date_range' => [],
        'region_ids' => null,
        'counter_ids' => null,
        'product_collection_id' => null,
    ];

    $sale = Sale::factory()->create([
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $saleItem = SaleItem::factory(5)->create([
        'sale_id' => $sale->id,
        'product_id' => $this->productA->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
        'quantity' => 15,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $this->productA->id,
    ]);

    $response = $this->productQueries->getFilteredTotalsForProfitsAndLossesReport($filterData, $this->companyA->id);

    expect($response->first())
        ->toHaveKey('total_quantity_returned', $saleReturnItem->quantity)
        ->toHaveKey('total_quantity_sold', collect($saleItem)->sum('quantity'))
        ->toHaveKey(
            'total_purchase_cost',
            CommonFunctions::numberFormat($this->productA->purchase_cost * $saleItem->sum('quantity'))
        );
});

test('getActivePaginatedRegularProductsForEcommerce method returns the list of products', function (): void {
    $filterData = [
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'after_updated_at' => null,
        'article_number' => null,
        'has_article_number' => null,
    ];

    $this->productA->type_id = ProductTypes::REGULAR_PRODUCT->value;
    $this->productA->is_available_in_ecommerce = true;
    $this->productA->save();

    $response = $this->productQueries->getActivePaginatedRegularProductsForEcommerce($this->companyA->id, $filterData);

    expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'name',
                'description',
                'code',
                'season_id',
                'department_id',
                'brand_id',
                'color_id',
                'size_id',
                'upc',
                'ean',
                'custom_sku',
                'manufacturer_sku',
                'article_number',
                'retail_price',
                'categories',
                'updated_at',
                'created_at',
                'tags',
                'status',
            ]
        );
});

test('a product can be added with non selling product store the data as expected', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $color = Color::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $size = Size::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $newProductRecord = Product::factory()->make([
        'company_id' => $this->companyA->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'is_non_selling_item' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ])->toArray();

    $category = Category::factory()->create([
        'company_id' => $this->companyA->id,
    ]);
    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $admin = Admin::factory()->create();

    $companyId = $newProductRecord['company_id'];

    $newProductRecord['images'] = [];
    $newProductRecord['videos'] = [];
    $newProductRecord['thumbnail'] = $uploadedFile;
    $newProductRecord['category_ids'] = [$category->id];
    $newProductRecord['tag_ids'] = [$tag->id];
    $newProductRecord['tiers'] = [];
    $newProductRecord['assembly_child_products'] = [];
    $newProductRecord['boxes'] = [];
    $newProductRecord['attached_templates'] = [];
    $newProductRecord['custom_field_values'] = null;
    $newProductRecord['retail_planning_hierarchy_id'] = null;
    $newProductRecord['warranty_month'] = null;
    $newProductRecord['vendor_id'] = null;
    $newProductRecord['is_warranty'] = false;
    $newProductRecord['original_created_at'] = null;

    $newProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;
    unset($newProductRecord['company_id']);

    $this->productQueries->addNew(new ProductData(...$newProductRecord), $companyId, $admin);

    unset($newProductRecord['videos'], $newProductRecord['images'], $newProductRecord['thumbnail'], $newProductRecord['category_ids'], $newProductRecord['tag_ids'], $newProductRecord['tiers'], $newProductRecord['boxes'], $newProductRecord['assembly_child_products'], $newProductRecord['custom_field_values'], $newProductRecord['attached_templates']);
    $newProductRecord['compound_product_name'] = $newProductRecord['name'] . ' ' . $color->getName() . ' ' . $size->getName();

    $this->assertDatabaseHas('products', [
        'name' => $newProductRecord['name'],
        'compound_product_name' => $newProductRecord['compound_product_name'],
        'company_id' => $this->companyA->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'is_non_selling_item' => true,
        'is_available_in_pos' => false,
        'is_available_in_ecommerce' => false,
    ]);
});

test('A product can be updated with non selling product update the data as expected', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $color = Color::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $size = Size::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $newProductRecord = Product::factory()->make([
        'color_id' => $color->id,
        'size_id' => $size->id,
        'is_non_selling_item' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ])->toArray();

    unset($newProductRecord['company_id']);
    $newProductRecord['images'] = [];
    $newProductRecord['videos'] = [];
    $newProductRecord['thumbnail'] = $uploadedFile;
    $newProductRecord['category_ids'] = [];
    $newProductRecord['tag_ids'] = [];
    $newProductRecord['tiers'] = [];
    $newProductRecord['assembly_child_products'] = [];
    $newProductRecord['boxes'] = [];
    $newProductRecord['attached_templates'] = [];
    $newProductRecord['custom_field_values'] = null;
    $newProductRecord['retail_planning_hierarchy_id'] = null;
    $newProductRecord['warranty_month'] = null;
    $newProductRecord['vendor_id'] = null;
    $newProductRecord['is_warranty'] = false;
    $newProductRecord['original_created_at'] = null;
    $newProductRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;

    $this->productQueries->update(new ProductData(...$newProductRecord), $this->productA->id, $this->companyA->id);

    unset($newProductRecord['videos'], $newProductRecord['images'], $newProductRecord['thumbnail'], $newProductRecord['category_ids'], $newProductRecord['tag_ids'], $newProductRecord['upc'], $newProductRecord['unit_of_measure_id'], $newProductRecord['tiers'], $newProductRecord['boxes'], $newProductRecord['assembly_child_products'], $newProductRecord['custom_field_values'], $newProductRecord['attached_templates']);
    $newProductRecord['compound_product_name'] = $newProductRecord['name'] . ' ' . $color->getName() . ' ' . $size->getName();

    $this->assertDatabaseHas('products', [
        'name' => $newProductRecord['name'],
        'compound_product_name' => $newProductRecord['compound_product_name'],
        'company_id' => $this->companyA->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
        'is_non_selling_item' => true,
        'is_available_in_pos' => false,
        'is_available_in_ecommerce' => false,
    ]);
});

test(
    'getFilteredProducts method returns the products id array based on filter by article number',
    function ($filterBy): void {
        $company = Company::factory()->create();
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'article_number' => 123,
            'is_non_selling_item' => false,
        ]);

        $filterData = [
            'filter_by' => $filterBy,
            'company_id' => $company->id,
        ];

        if (StockMovementFilters::BY_MASTER_PRODUCT->value === $filterBy) {
            $filterData['article_number'] = $product->article_number;
        }

        if (StockMovementFilters::BY_BRAND->value === $filterBy) {
            $brand = Brand::factory()->create();
            $product->brand_id = $brand->id;
            $product->save();
            $filterData['brand_ids'] = [$product->brand_ids];
        }

        if (StockMovementFilters::BY_CATEGORIES->value === $filterBy) {
            $category = Category::factory()->create();
            $product->categories()->attach([$category->id]);
            $filterData['category_ids'] = [$category->id];
        }

        if (StockMovementFilters::BY_DEPARTMENT->value === $filterBy) {
            $department = Department::factory()->create();
            $product->department_id = $department->id;
            $product->save();
            $filterData['department_id'] = $department->id;
        }

        $response = $this->productQueries->getFilteredProducts($filterData);
        if (StockMovementFilters::BY_PRODUCT->value || StockMovementFilters::BY_PRODUCTS->value) {
            expect($response)->toBeArray();

            return;
        }

        expect($response[0])->toBe($product->id);
    }
)->with([
    [StockMovementFilters::BY_MASTER_PRODUCT->value],
    [StockMovementFilters::BY_BRAND->value],
    [StockMovementFilters::BY_CATEGORIES->value],
    [StockMovementFilters::BY_DEPARTMENT->value],
    [StockMovementFilters::BY_PRODUCT->value],
    [StockMovementFilters::BY_PRODUCTS->value],
]);

test('addNewFromExternalProduct method call then new product create with relation', function (): void {
    $admin = Admin::factory()->create();

    $category = Category::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $color = Color::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $size = Size::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $product = Product::factory()->make([
        'company_id' => $this->companyA->id,
        'color_id' => $color->id,
        'size_id' => $size->id,
    ])->toArray();

    $product['category_ids'] = [$category->id];
    $product['tag_ids'] = [$tag->id];
    $product['sender_company'] = [];

    $this->productQueries->addNewFromExternalProduct($product, $admin);

    unset($product['category_ids'], $product['tag_ids'], $product['sender_company']);

    $this->assertDatabaseHas('products', $product);
    $this->assertDatabaseHas('product_tag', [
        'tag_id' => $tag->id,
    ]);
    $this->assertDatabaseHas('category_product', [
        'category_id' => $category->id,
        'sort_order' => 0,
    ]);
});

test('addNewFromExternalProductForVariant method call then new product create with relation', function (): void {
    Config::set('app.product_variant', true);

    $admin = Admin::factory()->create();

    $category = Category::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $tag = Tag::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $template = Template::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '098765434567',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        'is_non_selling_item' => false,
        'variant_template_id' => $template->id,
    ]);

    $attributeSize = Attribute::factory()->make([
        'id' => 1,
        'name' => 'size',
        'company_id' => $this->companyA->id,
    ]);

    $attributeColor = Attribute::factory()->make([
        'id' => 2,
        'name' => 'color',
        'company_id' => $this->companyA->id,
    ]);

    $product = Product::factory()->make([
        'company_id' => $this->companyA->id,
        'status' => Statuses::ACTIVE->value,
        'master_product_id' => $masterProduct->id,
    ]);

    $productVariantValue1 = ProductVariantValue::factory()->make([
        'id' => 1,
        'product_id' => $product->id,
        'attribute_id' => $attributeSize->id,
        'value' => 'sizeA',
    ])->toArray();

    $productVariantValue2 = ProductVariantValue::factory()->make([
        'id' => 2,
        'product_id' => $product->id,
        'attribute_id' => $attributeColor->id,
        'value' => 'colorA',
    ])->toArray();

    $productVariantValue1['attribute'] = $attributeSize->toArray();
    $productVariantValue2['attribute'] = $attributeColor->toArray();

    $product['product_variant_values'] = collect([$productVariantValue1, $productVariantValue2]);
    $masterProduct['product_variants'] = collect([$product]);

    $product['master_product'] = $masterProduct->toArray();
    $product['template_id'] = $template->id;
    $product['category_ids'] = [$category->id];
    $product['tag_ids'] = [$tag->id];

    $this->productQueries->addNewFromExternalProductForVariant($product->toArray(), $admin);

    $product = $product->toArray();

    unset($product['product_variant_values'], $product['master_product'], $product['template_id'], $product['category_ids'], $product['tag_ids']);

    $this->assertDatabaseHas('master_products', [
        'id' => $masterProduct['id'],
    ]);
});

test(
    'getDraftProductByIdsAndCompanyId method call then return draft status product list by productIds and companyId',
    function (): void {
        Config::set('app.product_variant', false);
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
        ]);

        $response = $this->productQueries->getDraftProductByIdsAndCompanyId(
            [$product->id],
            $this->companyA->id,
            Statuses::DRAFT->value
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('name', $product->name)
            ->toHaveKey('status', $product->status)
            ->toHaveKey('upc', $product->upc);
    }
);

test(
    'getDraftProductByIdsAndCompanyId method call then return draft status product list by productIds and companyId and product variant true',
    function (): void {
        Config::set('app.product_variant', true);
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
        ]);

        $response = $this->productQueries->getDraftProductByIdsAndCompanyId(
            [$product->id],
            $this->companyA->id,
            Statuses::DRAFT->value
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('name', $product->name)
            ->toHaveKey('status', $product->status)
            ->toHaveKey('upc', $product->upc);
    }
);

test(
    'A markAsApproved method call and change status in product as expected.',
    function (): void {
        Config::set('app.product_variant', false);
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
        ]);

        $admin = Admin::factory()->create();

        $this->productQueries->markAsApproved([$product->id], $this->companyA->id, $admin);

        $product->refresh();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'company_id' => $this->companyA->id,
            'name' => $product->name,
            'upc' => $product->upc,
            'status' => Statuses::ACTIVE->value,
        ]);
    }
);

test(
    'A markAsApproved method call and product variant true and change status in product as expected.',
    function (): void {
        Config::set('app.product_variant', true);
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
            'master_product_id' => $masterProduct->id,
        ]);

        $admin = Admin::factory()->create();

        $this->productQueries->markAsApproved([$product->id], $this->companyA->id, $admin);

        $product->refresh();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'company_id' => $this->companyA->id,
            'name' => $product->name,
            'upc' => $product->upc,
            'status' => Statuses::ACTIVE->value,
        ]);
    }
);

test(
    'A getDraftProductDetailsById method call and return response as expected.',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
        ]);

        $response = $this->productQueries->getDraftProductDetailsById($product->id, $this->companyA->id);

        expect($response->toArray())
            ->toHaveKey('name', $product->name)
            ->toHaveKey('code', $product->code)
            ->toHaveKey('status', Statuses::DRAFT->value);
    }
);

test(
    'A getYesterdayCreatedProductsIds methods return the product that are created yesterday as expected.',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
            'created_at' => Carbon::now()->yesterday(),
        ]);

        $response = $this->productQueries->getYesterdayCreatedProductsIds();

        expect(current($response))->toHaveKey('id', $product->getKey());
    }
);

test(
    'A getAllActiveProductsIds methods return all the product ids as expected.',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
        ]);

        $response = $this->productQueries->getAllActiveProductsIds();

        expect(end($response))->toHaveKey('id', $product->getKey());
    }
);

test(
    'A getMatchActiveProductsByDraftIdAndCompanyId methods return match active products by draftProductId and CompanyId.',
    function (): void {
        Config::set('app.product_variant', false);
        $draftProduct = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
        ]);
        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);
        $draftProduct->categories()->sync([$category->id]);

        $activeProduct = $draftProduct->replicate()->fill([
            'status' => Statuses::ACTIVE->value,
            'upc' => '123456',
            'code' => '23232323',
        ]);
        $activeProduct->save();
        $activeProduct->categories()->sync([$category->id]);

        $response = $this->productQueries->getMatchActiveProductsByDraftIdAndCompanyId(
            $draftProduct->id,
            $this->companyA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $activeProduct->getKey())
            ->toHaveKey('unit_of_measure_id', $activeProduct->unit_of_measure_id)
            ->toHaveKey('season_id', $activeProduct->season_id)
            ->toHaveKey('department_id', $activeProduct->department_id)
            ->toHaveKey('sub_department_id', $activeProduct->sub_department_id)
            ->toHaveKey('size_id', $activeProduct->size_id);
    }
);

test(
    'A getMatchActiveProductsByDraftIdAndCompanyId methods return match active products by draftProductId and CompanyId and product variant true.',
    function (): void {
        Config::set('app.product_variant', true);
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
            'status' => Statuses::DRAFT->value,
        ]);
        $draftProduct = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
            'master_product_id' => $masterProduct->id,
        ]);
        $category = Category::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $productVariantValue = ProductVariantValue::factory()->create([
            'product_id' => $draftProduct->id,
        ]);

        $masterProduct->categories()->sync([$category->id]);

        $activeProduct = $draftProduct->replicate()->fill([
            'status' => Statuses::DRAFT->value,
            'upc' => '123456',
            'code' => '23232323',
        ]);
        $activeProduct->save();
        $activeProduct->masterProduct->categories()->sync([$category->id]);

        $variantValue = $productVariantValue->replicate()->fill([
            'product_id' => $activeProduct->id,
        ]);

        $variantValue->save();

        $response = $this->productQueries->getMatchActiveProductsByDraftIdAndCompanyId(
            $draftProduct->id,
            $this->companyA->id
        );
        expect($response->first()->toArray())
            ->toHaveKey('id', $activeProduct->getKey())
            ->toHaveKey('name', $activeProduct->name)
            ->toHaveKey('type_id', $activeProduct->type_id);
    }
);

test(
    'A getCurrentUserProductCount methods return the count.',
    function (): void {
        Config::set('app.product_variant', false);
        $admin = Admin::factory()->create();
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
            'created_by_id' => $admin->id,
            'created_by_type' => ModelMapping::ADMIN->name,
        ]);

        $response = $this->productQueries->getCurrentUserProductCount([$product->id], $admin->id);

        expect($response)->toBe(1);
    }
);

test(
    'A getCurrentUserProductCount methods return the count and product variant true.',
    function (): void {
        Config::set('app.product_variant', true);
        $admin = Admin::factory()->create();

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
            'created_by_id' => $admin->id,
            'created_by_type' => ModelMapping::ADMIN->name,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => Statuses::DRAFT->value,
            'master_product_id' => $masterProduct->id,
        ]);

        $response = $this->productQueries->getCurrentUserProductCount([$product->id], $admin->id);

        expect($response)->toBe(1);
    }
);

test(
    'accumulatedSaleThroughSalesAndReturnsDataByProductUpcForCustomReport method returns the sold and returns of sales article numbers',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'location_id' => null,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'per_page' => null,
            'sort_by' => null,
            'search_text' => null,
            'include_by' => [],
            'filter_by' => 1,
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->accumulatedSaleThroughSalesAndReturnsDataByProductUpcForCustomReport(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test(
    'accumulatedSaleThroughInventoryDataByProductUpcForCustomReportForStoreWise method returns the sold and returns of sales article numbers',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'location_id' => null,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'per_page' => null,
            'sort_by' => null,
            'search_text' => null,
            'include_by' => [],
            'filter_by' => 1,
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->accumulatedSaleThroughInventoryDataByProductUpcForCustomReportForStoreWise(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test(
    'accumulatedSaleThroughSalesDataByProductUpcForCustomReportForStoreWise method returns the sold and returns of sales article numbers',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'location_id' => null,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'per_page' => null,
            'sort_by' => null,
            'search_text' => null,
            'include_by' => [],
            'filter_by' => 1,
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->accumulatedSaleThroughSalesDataByProductUpcForCustomReportForStoreWise(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test(
    'accumulatedSaleThroughReturnsDataByProductUpcForCustomReportForStoreWise method returns the sold and returns of sales article numbers',
    function (): void {
        $product = Product::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $counterId = Counter::factory()->create([
            'location_id' => $locationId,
        ])->id;

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counterId,
            'opened_by_pos_at' => Carbon::now(),
        ]);

        $filterData = [
            'location_id' => null,
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'per_page' => null,
            'sort_by' => null,
            'search_text' => null,
            'include_by' => [],
            'filter_by' => 1,
        ];

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $saleItems = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'is_exchange' => 0,
            'quantity' => 20,
            'total_price_paid' => 200,
            'sale_return_item_id' => null,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'original_sale_id' => $sale->id,
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItems->id,
            'product_id' => $product->id,
            'total_price_paid' => 100,
            'quantity' => 10,
        ]);

        $response = $this->productQueries->accumulatedSaleThroughReturnsDataByProductUpcForCustomReportForStoreWise(
            $filterData,
            $this->companyA->id
        );

        expect($response)->toBeInstanceOf(Collection::class);
    }
);

test('getUpcAndIsAvailableInEcommerceByUpc method return product', function (): void {
    $response = $this->productQueries->getUpcAndIsAvailableInEcommerceByUpc($this->productA->upc);

    expect($response->toArray())
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('upc', $this->productA->upc);
});

test('getIdByBrandIds method return product ids that have specific brands', function (): void {
    $response = $this->productQueries->getIdByBrandIds([$this->productA->brand_id], $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id);
});

test('getIdByStyleIds method return product ids that have specific styles', function (): void {
    $response = $this->productQueries->getIdByStyleIds([$this->productA->style_id], $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id);
});

test('getIdByDepartmentIds method return product ids that have specific departments', function (): void {
    $response = $this->productQueries->getIdByDepartmentIds([$this->productA->department_id], $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id);
});

test('getIdByCategoryIds method return product ids that have specific categories', function (): void {
    $category = Category::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $this->productA->categories()
        ->attach($category->id, [
            'sort_order' => 0,
        ]);

    $response = $this->productQueries->getIdByCategoryIds([$category->id], $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id);
});

test('getIdByProductCollectionIds method return product ids that have specific product collection', function (): void {
    $this->productCollection = ProductCollection::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABC',
        'number_of_products' => 1,
        'pending_products' => 0,
        'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
        'last_sync_at' => now()->format('Y-m-d H:i:s'),
        'status' => true,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => Admin::factory()->create()->id,
    ]);

    $this->productCollectionProduct = ProductCollectionProduct::factory()->create([
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $this->productA->id,
    ]);

    $this->productCollection->productCollectionProduct = collect([$this->productCollectionProduct]);

    $response = $this->productQueries->getIdByProductCollectionIds([$this->productCollection->id], $this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id);
});

test('A getByIdForEcommerce method call and return proper response', function (): void {
    $response = $this->productQueries->getByIdForEcommerce($this->productA->upc, $this->companyA->id);
    expect($response)->toBe($this->productA->id);
});

test(
    'getByIdsWithBrandAndCategoriesForEcommerce method returns the products list',
    function (): void {
        $response = $this->productQueries->getByIdsWithBrandAndCategoriesForEcommerce(
            [$this->productA->upc],
            [],
            $this->companyA->id
        );
        expect($response->first()->toArray())
            ->toHaveKey('id', $this->productA->id)
            ->toHaveKey('name', $this->productA->name)
            ->toHaveKey('upc', $this->productA->upc)
            ->toHaveKey('vendor_id', $this->productA->vendor_id)
            ->toHaveKeys(
                ['brand', 'categories', 'tags', 'tiers', 'unit_of_measure', 'boxes', 'vendor', 'color', 'size']
            );
    }
);

test('getProductsArticleNumberForEcommerce method return product', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ]);

    $response = $this->productQueries->getProductsArticleNumberForEcommerce($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('article_number', $product->article_number);
});

test('getPaginatedConsignmentReport method return products', function (bool $productVariant): void {
    Config::set('app.product_variant', $productVariant);

    $filterData = [
        'search_text' => null,
        'date_range' => null,
        'per_page' => 10,
    ];

    $vendor = Vendor::factory()->create([
        'company_id' => $this->companyA->id,
        'is_consignment' => true,
        'commission_percentage' => 5,
    ]);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
            'vendor_id' => $vendor->id,
        ]);
    }

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'vendor_id' => $vendor->id,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
        'master_product_id' => $productVariant ? $masterProduct->id : null,
    ]);

    $response = $this->productQueries->getPaginatedConsignmentReport($filterData, $this->companyA->id);

    if ($productVariant) {
        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('master_product_id', $masterProduct->id)
            ->toHaveKey('vendor_id', $product->vendor_id);
    } else {
        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('vendor_id', $product->vendor_id);
    }
})->with([[true], [false]]);

test('getConsignmentReportForExport method return products', function (bool $productVariant): void {
    Config::set('app.product_variant', $productVariant);

    $filterData = [
        'search_text' => null,
        'date_range' => null,
        'per_page' => 10,
    ];
    $vendor = Vendor::factory()->create([
        'company_id' => $this->companyA->id,
        'is_consignment' => true,
        'commission_percentage' => 5,
    ]);

    if ($productVariant) {
        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->companyA->id,
            'has_batch' => false,
            'is_non_inventory' => false,
            'vendor_id' => $vendor->id,
        ]);
    }

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'vendor_id' => $vendor->id,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
        'master_product_id' => $productVariant ? $masterProduct->id : null,
    ]);

    $response = $this->productQueries->getConsignmentReportForExport($filterData, $this->companyA->id);

    if ($productVariant) {
        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('master_product_id', $masterProduct->id)
            ->toHaveKey('vendor_id', $product->vendor_id);
    } else {
        expect($response->first()->toArray())
            ->toHaveKey('id', $product->id)
            ->toHaveKey('vendor_id', $product->vendor_id);
    }
})->with([[true], [false]]);

test('checkProductByUpc method check product is exist or not', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '12345',
        'status' => Statuses::ACTIVE->value,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
        'is_non_inventory' => false,
    ]);

    $response = $this->productQueries->checkProductByUpc($product->upc, $this->companyA->id);

    expect($response)->toBe(true);
});

test('getProductDetailsByArticleNumberForUploadImages method return product', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ]);

    $response = $this->productQueries->getProductDetailsByArticleNumberForUploadImages(
        $product->article_number,
        $this->companyA->id
    );

    expect($response->first()->toArray())
        ->toHaveKey('article_number', $product->article_number);
});

test('A product can be uploadImagesByArticleNumber', function (): void {
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '1234992999',
        'status' => Statuses::ACTIVE->value,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ]);

    $this->productQueries->uploadImagesByArticleNumber(new ProductImageUploadByArticleNumberData(...[
        'thumbnail' => $uploadedFile,
        'article_number' => $product->article_number,
    ]), $this->companyA->id);
    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::PRODUCT->name,
        'collection_name' => 'thumbnail',
        'mime_type' => 'image/jpeg',
    ]);
});

test('Get product name for export PDF headers', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '1234992999',
        'status' => Statuses::ACTIVE->value,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ]);

    $response = $this->productQueries->getProductNameForFilter([$product->id]);

    $this->assertIsString($response);
});

test(
    'getPaginatedOnlineProductsReport method returns the list of online products',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $company = Company::factory()->create([
            'name' => 'Order Test',
        ]);

        $date = Carbon::now();

        $employee = Employee::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->getKey(),
        ]);

        $location = Location::factory()->create([
            'company_id' => $company->getKey(),
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->create([
            'company_id' => $company->getKey(),
            'created_location_id' => $location->getKey(),
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $this->companyA->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $company->getKey(),
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $order = Order::factory()->create([
            'store_manager_id' => $storeManager->getKey(),
            'location_id' => $location->getKey(),
            'member_id' => $member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'sale_channel_id' => null,
            'created_at' => $date,
        ]);

        $orderItems = OrderItem::factory(3)->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'exchange_item_id' => null,
            'is_exchange' => 0,
            'complimentary_item_reason_id' => null,
            'box_product_id' => null,
            'quantity' => 30,
        ]);

        $orderReturn = OrderReturn::factory()->create([
            'store_manager_id' => $storeManager->getKey(),
            'location_id' => $location->getKey(),
            'member_id' => $member->getKey(),
            'original_order_id' => null,
        ]);

        $orderReturnItems = OrderReturnItem::factory()->create([
            'order_return_id' => $orderReturn->getKey(),
            'product_id' => $product->getKey(),
            'original_order_item_id' => null,
            'quantity' => 10,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'product_id' => null,
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'location_ids' => [$location->getKey()],
            'article_numbers' => null,
            'date_range' => [],
            'tag_ids' => null,
            'region_ids' => null,
            'product_collection_id' => null,
        ];

        $response = $this->productQueries->getPaginatedOnlineProductsReport($filterData, $company->id);

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
    }
)->with([[true], [false]]);

test(
    'getOnlineProductsReportForExport method returns the list of online products',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $company = Company::factory()->create([
            'name' => 'Order Test',
        ]);

        $date = Carbon::now();

        $employee = Employee::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->getKey(),
        ]);

        $location = Location::factory()->create([
            'company_id' => $company->getKey(),
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->create([
            'company_id' => $company->getKey(),
            'created_location_id' => $location->getKey(),
        ]);

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create([
                'company_id' => $company->id,
                'has_batch' => false,
                'is_non_inventory' => false,
            ]);
        }

        $product = Product::factory()->create([
            'company_id' => $company->getKey(),
            'compound_product_name' => $productVariant ? 'ABCD' : 'DEFG',
            'code' => $productVariant ? '8898998' : '12132465465',
            'status' => Statuses::ACTIVE->value,
            'master_product_id' => $productVariant ? $masterProduct->id : null,
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        if ($productVariant) {
            $product->masterProduct = $masterProduct;
        }

        $order = Order::factory()->create([
            'store_manager_id' => $storeManager->getKey(),
            'location_id' => $location->getKey(),
            'member_id' => $member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'sale_channel_id' => null,
            'created_at' => $date,
        ]);

        $orderItems = OrderItem::factory(3)->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'exchange_item_id' => null,
            'is_exchange' => 0,
            'complimentary_item_reason_id' => null,
            'box_product_id' => null,
            'quantity' => 30,
        ]);

        $orderReturn = OrderReturn::factory()->create([
            'store_manager_id' => $storeManager->getKey(),
            'location_id' => $location->getKey(),
            'member_id' => $member->getKey(),
            'original_order_id' => null,
        ]);

        $orderReturnItems = OrderReturnItem::factory()->create([
            'order_return_id' => $orderReturn->getKey(),
            'product_id' => $product->getKey(),
            'original_order_item_id' => null,
            'quantity' => 10,
        ]);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'product_id' => null,
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'location_ids' => [$location->getKey()],
            'article_numbers' => null,
            'date_range' => [],
            'tag_ids' => null,
            'region_ids' => null,
            'product_collection_id' => null,
        ];

        $response = $this->productQueries->getOnlineProductsReportForExport($filterData, $company->id);

        expect($response)->toBeInstanceOf(Collection::class);
    }
)->with([[true], [false]]);

test('validateProductSaleChannelMatch returns true when sale channel is linked', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $this->productA->saleChannels()->attach($saleChannel->id);

    $result = $this->productQueries->validateProductSaleChannelMatch($this->productA, $saleChannel);

    expect($result)->toBeTrue();
});

test('a product can be added for Integration', function (): void {
    $company = Company::factory()->create([
        'code' => '1234567',
        'creator_can_approve_draft_product' => true,
    ]);

    $productRecord = Product::factory()->make([
        'company_id' => $company->id,
    ])->toArray();

    $category = Category::factory()->create([
        'company_id' => $company->id,
    ]);

    $admin = Admin::factory()->create();

    $companyId = $productRecord['company_id'];

    $productRecord['category_ids'] = [$category->id];
    $productRecord['type_id'] = (string) ProductTypes::REGULAR_PRODUCT->value;

    $productData = new ProductDataForIntegration(
        $productRecord['name'],
        $productRecord['brand_id'],
        $productRecord['upc'],
        (int) $productRecord['type_id'],
        $productRecord['category_ids'],
        (float) $productRecord['retail_price'],
        $productRecord['article_number'],
        (float) $productRecord['purchase_cost'],
    );

    $this->productQueries->addNewProductForIntegration($productData, $companyId, $admin);

    $newProductData = $productData->toArray();
    unset($newProductData['category_ids']);
    $this->assertDatabaseHas('products', $newProductData);
});

test(
    'updateProductPrices method updates the product retail price according to id',
    function (): void {
        $company = Company::factory()->create([
            'name' => 'Order Test',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->getKey(),
        ]);

        $this->productQueries->updateProductPrices($this->productA->id, $this->companyA->id, [
            'retail_price' => 100,
            'franchise_price_1' => 100,
            'franchise_price_3' => 100,
            'franchise_price_2' => 100,
            'wholesale_price' => 100,
            'company_or_tender_price' => 100,
            'branch_price' => 100,
            'minimum_price' => 100,
            'original_capital_price' => 100,
            'capital_price' => 100,
            'staff_price' => 100,
        ], $storeManager);

        $this->assertDatabaseHas('products', [
            'id' => $this->productA->id,
            'retail_price' => 100,
        ]);
    }
);

test('getUpcById method return product UPC', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'article_number' => '1234',
        'upc' => 'ABCD1234',
        'status' => Statuses::ACTIVE->value,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => true,
    ]);

    $response = $this->productQueries->getUpcById($product->id);

    expect($response)->toBe($product->upc);
});

test('getAllByCompanyId returns the Products details', function (): void {
    $response = $this->productQueries->getAllByCompanyId($this->companyA->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('name', $this->productA->name);
});

test('getAllByCompanyId returns the Products details when product_variant true', function (): void {
    Config::set('app.product_variant', true);
    $response = $this->productQueries->getAllByCompanyId($this->companyA->id);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->productA->id)
        ->toHaveKey('name', $this->productA->name);
});

test('getByIdWithProductVariantValues method call and proper response', function (): void {
    $response = $this->productQueries->getByIdWithProductVariantValues($this->productA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->productA->name)
        ->toHaveKey('retail_price', $this->productA->retail_price);
});

test('getCompanyActiveRegularProductCount returns the count of Products', function (): void {
    $response = $this->productQueries->getCompanyActiveRegularProductCount($this->companyA->id);

    expect($response)->toBe(1);
});

test('it can create or update a product from Azentio item', function (): void {
    $company = Company::factory()->create([
        'name' => 'Order Test',
    ]);

    $product = Product::factory()->make([
        'company_id' => $company->getKey(),
        'upc' => 'ABCD1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $productData = new AzentioItemData(
        $product->upc,
        $company->getKey(),
        $product->brand_id,
        $product->name,
        null,
        null,
    );

    $this->productQueries->createOrUpdateProductFromAzentioItem($productData->toArray());

    $this->assertDatabaseHas(Product::class, [
        'upc' => $product->upc,
        'company_id' => $company->getKey(),
        'name' => $product->name,
    ]);

    $productData = new AzentioItemData(
        $product->upc,
        $company->getKey(),
        $product->brand_id,
        'Hey New Product Name',
        'No Color',
        'No Size',
    );

    $this->productQueries->createOrUpdateProductFromAzentioItem($productData->toArray());

    $this->assertDatabaseHas(Product::class, [
        'upc' => $product->upc,
        'company_id' => $company->getKey(),
        'name' => 'Hey New Product Name',
    ]);
});

test('getProductIds returns the product ids', function (): void {
    Config::set('app.product_variant', false);

    Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'XYZ',
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $response = $this->productQueries->getProductIds([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'product_collection_ids' => null,
        'product_sync_type_id' => ProductSyncTypes::ALL_PRODUCT->value,
        'attributes' => null,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->productA->id);
});
