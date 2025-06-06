<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Brand\DataObjects\BrandData;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\GeneralSalesReportTypes;
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
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Size;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->brandA = Brand::factory()->create([
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    $this->brandB = Brand::factory()->create([
        'name' => 'BCDE',
        'code' => 'BCDE',
    ]);

    $this->brandQueries = new BrandQueries();
});

test('Brands can be searched', function (): void {
    $response = $this->brandQueries->listQuery([
        'search_text' => 'AB',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKey('code', $this->brandA->code);
});

test('Brands can be sorted by name', function (): void {
    $response = $this->brandQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ]);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKey('code', $this->brandA->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->brandB->name)
        ->toHaveKey('code', $this->brandB->code);
});

test('Brands are returned as per page', function (): void {
    $response = $this->brandQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ]);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->brandB->name)
        ->toHaveKey('code', $this->brandB->code);
});

test('New brand can be added', function (): void {
    $this->brandQueries->addNew(new BrandData('XYZ', 'XYZ123'));

    $this->assertDatabaseHas('brands', [
        'name' => 'XYZ',
        'code' => 'XYZ123',
    ]);
});

test('A brand can be fetched', function (): void {
    $response = $this->brandQueries->getById($this->brandA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKey('code', $this->brandA->code);
});

test('A brand can be updated', function (): void {
    $this->brandQueries->update(new BrandData('XYZ', 'XYZ123'), $this->brandA->id);

    $this->assertDatabaseHas('brands', [
        'name' => 'XYZ',
        'code' => 'XYZ123',
    ]);
});

test('It call method getWithBasicColumns and return get basic brand details', function (): void {
    $brand = Brand::factory()->create();

    $response = $this->brandQueries->getWithBasicColumns();
    $this->assertContains([
        'id' => $brand->id,
        'name' => $brand->name,
    ], $response->toArray());
});

test('getCompanyBrands method returns the brands of the specified company', function (): void {
    $brand = Brand::factory()->create();
    $company = Company::factory()->create();
    $company->brands()->attach($brand->id);

    $response = $this->brandQueries->getCompanyBrands($company->id);
    $this->assertContains([
        'id' => $brand->id,
        'name' => $brand->name,
    ], $response->toArray());
});

test('getIdByName method returns the brand details', function (): void {
    $response = $this->brandQueries->getIdByName($this->brandA->name);
    $this->assertEquals($this->brandA->id, $response);
});

test('existsByName method returns result as expected', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $response = $this->brandQueries->existsByName($this->brandA->name, $company->id);
    $this->assertTrue($response);

    $response = $this->brandQueries->existsByName($this->brandB->name, $company->id);
    $this->assertFalse($response);

    $response = $this->brandQueries->existsByName('ABCDEFGH', $company->id);
    $this->assertFalse($response);
});

test('existsByNames method returns result as expected', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $response = $this->brandQueries->existsByNames([$this->brandA->name], $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->brandA->id)
        ->toHaveKey('name', $this->brandA->name);

    $response = $this->brandQueries->existsByNames([$this->brandB->name], $company->id);
    $this->assertEquals($response, new Collection([]));
});

test('brands can be searched by name', function (): void {
    $company = Company::factory()->create();

    $brand = Brand::factory()->create([
        'name' => 'my_brand',
    ]);

    $company->brands()->attach($brand->id);

    $response = $this->brandQueries->getFilteredBrandsByCompanyId('my_brand', $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $brand->id)
        ->toHaveKey('name', $brand->name);
});

test(
    'The test is checking the functionality of the "getIdsByNames" method of the "brandQueries" object.',
    function (): void {
        $company = Company::factory()->create();

        $brand = Brand::factory()->create([
            'name' => 'my_brand',
        ]);

        $company->brands()->attach($brand->id);

        $response = $this->brandQueries->getIdsByNames(['my_brand'], $company->id);

        $this->assertEquals([$brand->id], $response);
    }
);

test(
    'getCachedBrandsSalesForChart method returns result as expected',
    function (): void {
        $data = now();

        $company = Company::factory()->create();

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $brand = Brand::factory()->create([
            'name' => 'my_brand',
        ]);

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
        ]);

        $location->brands()->sync($product->brand_id);

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
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        $company->brands()->attach($brand->id);

        Cache::forget('cache-brands-sales-' . $location->id . $data->format('Y-m-d'));

        $response = $this->brandQueries->getCachedBrandsSalesForChart(
            $company->id,
            $location->id,
            null,
            $data->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $brand->id)
            ->toHaveKey('name', $brand->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-brands-sales-' . $location->id . $data->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->brandQueries->getCachedBrandsSalesForChart(
            $company->id,
            $location->id,
            null,
            $data->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedBrandsSalesForChart method returns result as expected while using the brand selection',
    function (): void {
        $data = now();

        $company = Company::factory()->create();

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $brand = Brand::factory()->create([
            'name' => 'my_brand',
        ]);

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
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
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        $company->brands()->attach($brand->id);

        Cache::forget('cache-brands-sales-' . $location->id . $data->format('Y-m-d'));

        $response = $this->brandQueries->getCachedBrandsSalesForChart(
            $company->id,
            $location->id,
            $brand->id,
            $data->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $brand->id)
            ->toHaveKey('name', $brand->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-brands-sales-' . $location->id . $brand->id . $data->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->brandQueries->getCachedBrandsSalesForChart(
            $company->id,
            $location->id,
            $brand->id,
            $data->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getMonthWiseBrandsSales method returns result as expected',
    function (): void {
        $data = now();

        $company = Company::factory()->create();

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $brand = Brand::factory()->create([
            'name' => 'my_brand',
        ]);

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'total_tax_amount' => 10.00,
            'quantity' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $company->brands()->attach($brand->id);
        $locationId = null;
        Cache::forget('cache-month-wise-brands-sales-' . $company->id . '-' . $locationId. '-' . $brand->id);

        $response = $this->brandQueries->getMonthWiseBrandsSales($company->id, $locationId, $brand->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $brand->id)
            ->toHaveKey('name', $brand->name)
            ->toHaveKey('total_amount', 20);

        expect(
            Cache::has('cache-month-wise-brands-sales-' . $company->id . '-' . $locationId . '-' . $brand->id)
        )->toBeTrue();

        $cachedResponse = $this->brandQueries->getMonthWiseBrandsSales($company->id, $locationId, $brand->id);

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getMonthWiseBrandsSaleReturns method returns result as expected',
    function (): void {
        $data = now();

        $company = Company::factory()->create();

        $location = Location::factory()->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        $brand = Brand::factory()->create([
            'name' => 'my_brand',
        ]);

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
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
            'happened_at' => $data->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        $company->brands()->attach($brand->id);
        $locationId = null;
        Cache::forget('cache-month-wise-brands-sale-returns-' . $company->id . '-' . $locationId. '-' . $brand->id);

        $response = $this->brandQueries->getMonthWiseBrandsSaleReturns($company->id, $locationId, $brand->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $brand->id)
            ->toHaveKey('name', $brand->name)
            ->toHaveKey('total_amount', 10);

        expect(
            Cache::has('cache-month-wise-brands-sale-returns-' . $company->id . '-' . $locationId . '-' . $brand->id)
        )->toBeTrue();

        $cachedResponse = $this->brandQueries->getMonthWiseBrandsSaleReturns($company->id, $locationId, $brand->id);

        expect($cachedResponse)->toEqual($response);
    }
);

test('it returns the sales summary for a brand within a specific category and date', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $categoryId = Category::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'brand_id' => $this->brandA->id,
    ]);

    $product->categories()->attach([$categoryId]);

    $locationId = Location::factory()->create([
        'company_id' => $company->id,
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

    $response = $this->brandQueries->getBrandSalesSummary($filterData, $company->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a brand within a specific color and date', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $colorId = Color::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'brand_id' => $this->brandA->id,
        'color_id' => $colorId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $company->id,
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

    $response = $this->brandQueries->getBrandSalesSummary($filterData, $company->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a brand within a specific color group and date', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $colorGroupId = ColorGroup::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
    ])->id;

    $colorId = Color::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
        'group_id' => $colorGroupId,
    ])->id;

    $product = Product::factory()->create([
        'brand_id' => $this->brandA->id,
        'color_id' => $colorId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $company->id,
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

    $response = $this->brandQueries->getBrandSalesSummary($filterData, $company->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a brand within a specific department and date', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $departmentId = Department::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'brand_id' => $this->brandA->id,
        'department_id' => $departmentId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $company->id,
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

    $response = $this->brandQueries->getBrandSalesSummary($filterData, $company->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a brand within a specific size and date', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $sizeId = Size::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'brand_id' => $this->brandA->id,
        'size_id' => $sizeId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $company->id,
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

    $response = $this->brandQueries->getBrandSalesSummary($filterData, $company->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a brand within a specific style and date', function (): void {
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $styleId = Style::factory()->create([
        'company_id' => $company->id,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'brand_id' => $this->brandA->id,
        'style_id' => $styleId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $company->id,
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

    $response = $this->brandQueries->getBrandSalesSummary($filterData, $company->id);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->brandA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it retrieves a collection of brands by their IDs', function (): void {
    $brandId = Brand::factory()->create()->id;

    $response = $this->brandQueries->getByIds([$brandId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test(
    'firstOrCreateByName method return brand id',
    function (): void {
        $company = Company::factory()->create();
        $response = $this->brandQueries->firstOrCreateByName($this->brandA->name, $company->id);
        $this->assertEquals($this->brandA->id, $response);
    }
);

test(
    'getSalesRecordsGroupedByBrandAndRegion method returns the sales brands and region wise',
    function (): void {
        $company = Company::factory()->create();

        $company->brands()->attach($this->brandA->getKey());

        $product = Product::factory()->create([
            'brand_id' => $this->brandA->getKey(),
            'company_id' => $company->getKey(),
        ]);

        $region = Region::factory()->create([
            'company_id' => $company->getKey(),
        ]);

        $locationId = Location::factory()->create([
            'company_id' => $company->getKey(),
            'region_id' => $region->getKey(),
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
            'location_ids' => [],
            'department_ids' => [],
            'brand_ids' => [],
            'date' => $counterUpdate->opened_by_pos_at->format('Y-m-d'),
            'filter_by' => null,
            'report_type' => GeneralSalesReportTypes::BY_CURRENT_DAY_VS_PREVIOUS_DAY->value,
            'promoter_ids' => [],
            'counter_ids' => [],
            'exclude_products_with_no_price' => true,
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

        $response = $this->brandQueries->getSalesRecordsGroupedByBrandAndRegion(
            $filterData,
            $company->getKey(),
            false,
        );

        expect($response)->toBeInstanceOf(SupportCollection::class);
    }
);

test('the getBrandsExport method calls and return response as expected.', function (): void {
    $response = $this->brandQueries->getBrandsExport([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
    ]);

    expect($response->first()->toArray())
        ->toHaveKeys(['name', 'code']);
});

test('Get Brand name for export PDF headers', function (): void {
    $response = $this->brandQueries->getBrandNameForFilter([$this->brandA->id]);

    $this->assertIsString($response);
});

test('getAllByCompanyId returns the Brands details', function (): void {
    $this->brandB->delete();
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $response = $this->brandQueries->getAllByCompanyId($company->id);

    expect($response->count())->toBe(1);
    expect($response->toArray()[0])->toHaveKey('id', $this->brandA->id);
});

test('getIdByFirstCompanyBrand returns the first company brand id', function (): void {
    $this->brandB->delete();
    $company = Company::factory()->create();
    $company->brands()->attach($this->brandA->id);

    $response = $this->brandQueries->getIdByFirstCompanyBrand($company->id);

    expect($response)->toBe($this->brandA->id);
});
