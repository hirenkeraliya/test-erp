<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\DataObjects\CategoryData;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use App\Models\Size;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->categoryA = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->categoryB = Category::factory()->create([
        'company_id' => $this->companyId,
        'parent_category_id' => $this->categoryA->id,
    ]);

    $this->categoryQueries = new CategoryQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('It can fetch categories with children', function (): void {
    $response = $this->categoryQueries->listQuery($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('parent_category_id', $this->categoryA->parent_category_id)
        ->toHaveKey('company_id', $this->categoryA->company_id)
        ->toHaveKey('children.0.id', $this->categoryB->id)
        ->toHaveKey('children.0.name', $this->categoryB->name);
});

test('getById method returns category record.', function (): void {
    $response = $this->categoryQueries->getById($this->categoryA->id, $this->categoryA->company_id);

    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'name',
                'code',
                'parent_category_id',
                'description',
                'status',
                'is_available_in_ecommerce',
                'is_display_on_menu',
            ]
        );
});

test('New category can be added', function (): void {
    $categoryData['name'] = 'ABCD';
    $categoryData['code'] = 'ABCD001';
    $categoryData['description'] = 'ABCD001';
    $categoryData['status'] = true;
    $categoryData['is_available_in_ecommerce'] = true;
    $categoryData['is_display_on_menu'] = true;
    $categoryData['square_image'] = null;
    $categoryData['portrait_images'] = [];
    $categoryData['landscape_images'] = [];
    $categoryData['parent_category_id'] = null;

    $this->categoryQueries->addNew(new CategoryData(...$categoryData), $this->companyId);

    $this->assertDatabaseHas('categories', [
        'company_id' => $this->companyId,
        'parent_category_id' => null,
        'name' => 'ABCD',
        'code' => 'ABCD001',
    ]);
});

test('New sub category can be added', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $categoryData['name'] = 'EFGH';
    $categoryData['code'] = 'EFGH001';
    $categoryData['square_image'] = $uploadedFile;
    $categoryData['status'] = true;
    $categoryData['is_available_in_ecommerce'] = true;
    $categoryData['is_display_on_menu'] = true;
    $categoryData['portrait_images'] = [];
    $categoryData['landscape_images'] = [];
    $categoryData['parent_category_id'] = $this->categoryB->id;
    $categoryData['description'] = 'ABCD001';

    $this->categoryQueries->addNew(new CategoryData(...$categoryData), $this->companyId);

    $this->assertDatabaseHas('media', [
        'model_type' => ModelMapping::CATEGORY->name,
        'collection_name' => 'square_image',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertDatabaseHas('categories', [
        'company_id' => $this->companyId,
        'parent_category_id' => $this->categoryB->id,
        'name' => 'EFGH',
        'code' => 'EFGH001',
    ]);
});

test('A category can be updated', function (): void {
    $categoryData['name'] = 'EFGHI';
    $categoryData['code'] = 'EFGHI1234';
    $categoryData['status'] = true;
    $categoryData['is_available_in_ecommerce'] = false;
    $categoryData['is_display_on_menu'] = false;
    $categoryData['square_image'] = null;
    $categoryData['portrait_images'] = [];
    $categoryData['landscape_images'] = [];
    $categoryData['parent_category_id'] = $this->categoryB->parent_category_id;
    $categoryData['description'] = 'ABCD001';

    $this->categoryQueries->update(new CategoryData(...$categoryData), $this->categoryB->id, $this->companyId);

    $this->assertDatabaseHas('categories', [
        'company_id' => $this->categoryB->company_id,
        'parent_category_id' => $this->categoryB->parent_category_id,
        'name' => 'EFGHI',
        'code' => 'EFGHI1234',
    ]);
});

test('categories can be fetched', function (): void {
    $response = $this->categoryQueries->getMainCategoriesWithBasicColumns($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->categoryA->id)
        ->toHaveKey('name', $this->categoryA->name);
});

test('child categories can be fetched', function (): void {
    $response = $this->categoryQueries->getChildCategoriesWithBasicColumns($this->categoryA->id, $this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->categoryB->id)
        ->toHaveKey('name', $this->categoryB->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->categoryQueries->existsByName($this->categoryA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->categoryQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('existsByNameAndCompanyId method returns result as expected', function (): void {
    $response = $this->categoryQueries->existsByNameAndCompanyId($this->categoryA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->categoryQueries->existsByNameAndCompanyId('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns the category details', function (): void {
    $response = $this->categoryQueries->getIdByName($this->categoryA->name, $this->companyId);
    $this->assertEquals($this->categoryA->id, $response);
});

test('doAllParentCategoriesExist checks for category existence as expected', function (): void {
    $response = $this->categoryQueries->doAllParentCategoriesExist($this->companyId, [$this->categoryA->id]);

    expect($response)->toBeTrue();

    $response = $this->categoryQueries->doAllParentCategoriesExist($this->companyId, [$this->categoryB->id]);

    expect($response)->toBeFalse();
});

test('categories can be searched by name', function (): void {
    $category = Category::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'my_category',
    ]);

    $response = $this->categoryQueries->getFilteredCategoriesByCompanyId('my_category', $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $category->id)
        ->toHaveKey('name', $category->name);
});

test('It can fetch categories by company id', function (): void {
    $response = $this->categoryQueries->getByCompanyId($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->categoryA->id)
        ->toHaveKey('name', $this->categoryA->name);
});

test('It can fetch pos categories by company id', function (): void {
    $response = $this->categoryQueries->getByCompanyIdForPos($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->categoryA->id)
        ->toHaveKey('name', $this->categoryA->name)
        ->toHaveKey('status', $this->categoryA->status);
});

test('getSaleItemsTotalSum method category wise sale data', function (): void {
    $data = now();
    $companyId = $this->companyId;
    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);

    $category = Category::factory()->create([
        'company_id' => $companyId,
    ]);

    $product->categories()
        ->attach($category->id, [
            'sort_order' => 0,
        ]);

    $location = Location::factory()->create([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->getKey(),
        'opened_by_pos_at' => $data->format('Y-m-d H:i:s'),
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'status' => SaleStatus::REGULAR_SALE->value,
        'happened_at' => $data->format('Y-m-d H:i:s'),
        'layaway_pending_amount' => null,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'is_exchange' => 0,
        'sale_return_item_id' => null,
    ]);

    $result = $this->categoryQueries->getSaleItemsTotalSum(
        now()->startOfDay()->format('Y-m-d H:i:s'),
        now()->endOfDay()->format('Y-m-d H:i:s')
    );

    expect($result)->toBeInstanceOf(Collection::class);

    expect($result->first())->toHaveKey('location_id', $location->id)
        ->toHaveKey('company_id', $this->companyId);
});

test('getSaleReturnItemsTotalSum method category wise sale data', function (): void {
    $location = Location::factory()->create([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->getKey(),
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $category = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $product = Product::factory()->create();

    $product->categories()
        ->attach($category->id, [
            'sort_order' => 0,
        ]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
        'quantity' => 10,
        'total_price_paid' => 100,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);

    $response = $this->categoryQueries->getSaleReturnItemsTotalSum(
        now()->startOfDay()->format('Y-m-d H:i:s'),
        now()->endOfDay()->format('Y-m-d H:i:s')
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $category->id)
        ->toHaveKey('name', $category->name)
        ->toHaveKey('total_return_sale_amount', 100)
        ->toHaveKey('total_return_units', 10)
        ->toHaveKey('location_id', $location->id)
        ->toHaveKey('company_id', $this->companyId);
});

test(
    'getCachedCategoriesSalesForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'id' => 1,
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        $category = Category::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $product = Product::factory()->create();

        $location->brands()->sync($product->brand_id);

        $product->categories()
            ->attach($category->id, [
                'sort_order' => 0,
            ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'quantity' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget('cache-category-sales-' . $location->id . now()->format('Y-m-d'));

        $response = $this->categoryQueries->getCachedCategoriesSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $category->id)
            ->toHaveKey('name', $category->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-category-sales-' . $location->id . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->categoryQueries->getCachedCategoriesSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedCategoriesSalesForChart method returns result as expected with brand selection',
    function (): void {
        $location = Location::factory()->create([
            'id' => 1,
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        $category = Category::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'my_brand',
        ])->id;

        $product = Product::factory()->create([
            'brand_id' => $brandId,
        ]);

        $product->categories()
            ->attach($category->id, [
                'sort_order' => 0,
            ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'quantity' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget('cache-category-sales-' . $location->id . $brandId . now()->format('Y-m-d'));

        $response = $this->categoryQueries->getCachedCategoriesSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $category->id)
            ->toHaveKey('name', $category->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-category-sales-' . $location->id . $brandId . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->categoryQueries->getCachedCategoriesSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);

        $cachedResponse = $this->categoryQueries->getCachedCategoriesSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->not->toBe($response);
    }
);

test('It can fetch parent categories by company id', function (): void {
    $response = $this->categoryQueries->getParentByCompanyId($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->categoryA->id)
        ->toHaveKey('name', $this->categoryA->name);
});

test('existsByCode method return the stores name and code', function (): void {
    $response = $this->categoryQueries->existsByCode((string) $this->categoryA->code, $this->companyId);
    $this->assertTrue($response);

    $response = $this->categoryQueries->existsByCode('123', $this->companyId);
    $this->assertFalse($response);
});

test('it retrieves a collection of categories by their IDs', function (): void {
    $categoryId = Category::factory()->create()->id;

    $response = $this->categoryQueries->getByIds([$categoryId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test(
    'it returns the sales summary for a category within a specific color and date',
    function (): void {
        $colorId = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'color_id' => $colorId,
        ]);

        $product->categories()
            ->attach($this->categoryA->id, [
                'sort_order' => 0,
            ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $location->id,
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

        $response = $this->categoryQueries->getCategorySalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->categoryA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a category within a specific brand and date',
    function (): void {
        $brandId = Brand::factory()->create([
            'name' => 'test',
        ])->id;

        $this->company->brands()->attach($brandId);

        $product = Product::factory()->create([
            'brand_id' => $brandId,
        ]);

        $product->categories()
            ->attach($this->categoryA->id, [
                'sort_order' => 0,
            ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $location->id,
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

        $response = $this->categoryQueries->getCategorySalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->categoryA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a category within a specific color group and date',
    function (): void {
        $colorGroupId = ColorGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $colorId = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
            'group_id' => $colorGroupId,
        ])->id;

        $product = Product::factory()->create([
            'color_id' => $colorId,
        ]);

        $product->categories()
            ->attach($this->categoryA->id, [
                'sort_order' => 0,
            ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $location->id,
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

        $response = $this->categoryQueries->getCategorySalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->categoryA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a category within a specific size and date',
    function (): void {
        $sizeId = Size::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'size_id' => $sizeId,
        ]);

        $product->categories()
            ->attach($this->categoryA->id, [
                'sort_order' => 0,
            ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

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

        $response = $this->categoryQueries->getCategorySalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->categoryA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a category within a specific style and date',
    function (): void {
        $styleId = Style::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'style_id' => $styleId,
        ]);

        $product->categories()
            ->attach($this->categoryA->id, [
                'sort_order' => 0,
            ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counterId = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

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

        $response = $this->categoryQueries->getCategorySalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->categoryA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test('it returns the sales summary for a category within a specific department and date', function (): void {
    $departmentId = Department::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'department_id' => $departmentId,
    ]);

    $product->categories()
        ->attach($this->categoryA->id, [
            'sort_order' => 0,
        ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counterId = Counter::factory()->create([
        'location_id' => $location->id,
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

    $response = $this->categoryQueries->getCategorySalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->categoryA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the category details based on company id', function (): void {
    $filterData = [
        'per_page' => 10,
        'page' => 1,
        'after_updated_at' => null,
        'sort_by' => null,
        'sort_direction' => null,
    ];

    Category::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->categoryQueries->getCategoriesByCompanyId($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
});

test(
    'getIdByNameAndCompanyId method return category id',
    function (): void {
        $response = $this->categoryQueries->getIdByNameAndCompanyId($this->categoryA->name, $this->companyId);
        $this->assertEquals($this->categoryA->id, $response);
    }
);

test('it remove category image', function (): void {
    Storage::fake('public');

    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $category = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $category->addMedia($uploadedFile)->toMediaCollection('landscape_image');

    $this->categoryQueries->removeImage($category->id, $category->id, $this->companyId, 'landscape_image');

    $this->assertDatabaseMissing('media', [
        'model_type' => $category::class,
        'model_id' => $category->id,
        'collection_name' => 'image',
        'file_name' => $uploadedFile->name,
        'mime_type' => 'image/jpeg',
    ]);
});

test(
    'getCachedSeasonalTopFiveCategoriesSalesForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'id' => 1,
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->getKey(),
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $date = Carbon::now()->endOfDay();

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $category = Category::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $product = Product::factory()->create();

        $location->brands()->sync($product->brand_id);

        $product->categories()
            ->attach($category->id, [
                'sort_order' => 0,
            ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'quantity' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget(
            'cache-seasonal-category-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d')
        );

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => null,
            'location_id' => $location->id,
        ];

        $response = $this->categoryQueries->getCachedSeasonalTopFiveCategoriesSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $category->id)
            ->toHaveKey('name', $category->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has(
                'cache-seasonal-category-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d')
            )
        )->toBeTrue();

        $cachedResponse = $this->categoryQueries->getCachedSeasonalTopFiveCategoriesSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test('A getCategoryByIdAndCompanyId method call and return proper response', function (): void {
    $response = $this->categoryQueries->getCategoryByIdAndCompanyId($this->categoryA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->categoryA->name);
});

test('getCategoriesForBulkUpdate method call and return proper response', function (): void {
    $response = $this->categoryQueries->getCategoriesForBulkUpdate($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->categoryA->id)
        ->toHaveKey('name', $this->categoryA->name)
        ->toHaveKey('code', $this->categoryA->code)
        ->toHaveKey('description', $this->categoryA->description)
        ->toHaveKey('status', $this->categoryA->status)
        ->toHaveKey('is_available_in_ecommerce', $this->categoryA->is_available_in_ecommerce)
        ->toHaveKey('is_display_on_menu', $this->categoryA->is_display_on_menu);
});

test('codeTakenByAnotherCategory method returns boolean as expected', function (): void {
    $response = $this->categoryQueries->codeTakenByAnotherCategory(
        $this->categoryA->code,
        $this->categoryA->name,
        $this->companyId
    );
    $this->assertFalse($response);

    $category = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->categoryQueries->codeTakenByAnotherCategory(
        $category->code,
        $this->categoryA->name,
        $this->companyId
    );
    $this->assertTrue($response);
});

test('A category can be updated by name', function (): void {
    $this->categoryQueries->updateByName(
        [
            'company_id' => $this->companyId,
            'name' => 'tests',
            'code' => '123456',
        ],
        $this->categoryA->name,
        $this->companyId
    );

    $this->assertDatabaseHas('categories', [
        'company_id' => $this->companyId,
        'name' => 'tests',
        'code' => '123456',
    ]);
});

test('it calls getIdByNameWithoutCompanyId to fetch the category', function (): void {
    $response = $this->categoryQueries->getIdByNameWithoutCompanyId($this->categoryA->name);

    expect($response)->toHaveKey('id', $this->categoryA->getKey());
});

test('it calls updateIsAvailableInEcommerce to update is available in ecommerce', function (): void {
    $this->assertDatabaseHas(Category::class, [
        'id' => $this->categoryA->getKey(),
        'is_available_in_ecommerce' => $this->categoryA->is_available_in_ecommerce,
    ]);

    $this->categoryQueries->updateIsAvailableInEcommerce($this->categoryA);

    $this->assertDatabaseHas(Category::class, [
        'id' => $this->categoryA->getKey(),
        'is_available_in_ecommerce' => $this->categoryA->is_available_in_ecommerce,
    ]);
});

test('Category record returns by id', function (): void {
    $response = $this->categoryQueries->getCategoryById($this->categoryA->id);

    expect($response->toArray())
        ->toHaveKeys(
            ['id', 'name', 'is_available_in_ecommerce', 'company_id', 'status', 'is_display_on_menu', 'description']
        );
});

test('Get Category name for export PDF headers', function (): void {
    $response = $this->categoryQueries->getCategoryNameForFilter([$this->categoryA->id]);

    $this->assertIsString($response);
});

test('Category record returns by id for ecommerce', function (): void {
    $response = $this->categoryQueries->getCategoryByIdForEcommerce($this->categoryA->id);

    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'name',
                'is_available_in_ecommerce',
                'company_id',
                'status',
                'is_display_on_menu',
                'description',
                'code',
                'is_display_on_menu',
            ]
        );
});
