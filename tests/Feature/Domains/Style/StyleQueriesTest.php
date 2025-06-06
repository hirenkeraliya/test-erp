<?php

declare(strict_types=1);

use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Style\DataObjects\StyleData;
use App\Domains\Style\StyleQueries;
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
use App\Models\Size;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->styleA = Style::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
    ]);

    $this->styleB = Style::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
    ]);

    $this->styleQueries = new StyleQueries();
});

test('Styles can be searched', function (): void {
    $response = $this->styleQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->styleA->name);
});

test("Styles are returned as per admin's company", function (): void {
    Style::factory()->create();

    $response = $this->styleQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->styleB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->styleA->name);
});

test('Styles are returned as per page', function (): void {
    $response = $this->styleQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->styleB->name);
});

test('Styles can be sorted by id', function (): void {
    $response = $this->styleQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->styleA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->styleB->name);
});

test('new style can be added', function (): void {
    $this->styleQueries->addNew(new StyleData('styleName', 'styleCode'), $this->companyId);

    $this->assertDatabaseHas('styles', [
        'name' => 'styleName',
        'code' => 'styleCode',
        'company_id' => $this->companyId,
    ]);
});

test('A style can be fetched', function (): void {
    $response = $this->styleQueries->getById($this->styleA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKey('code', $this->styleA->code);
});

test('A style can be updated', function (): void {
    $this->styleQueries->update(
        new StyleData('styleNameUpdate', 'styleCodeUpdate'),
        $this->styleA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('styles', [
        'name' => 'styleNameUpdate',
        'code' => 'styleCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('styles can be fetched', function (): void {
    $response = $this->styleQueries->getWithBasicColumns($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->styleA->id)
        ->toHaveKey('name', $this->styleA->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->styleQueries->existsByName($this->styleA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->styleQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns style details', function (): void {
    $response = $this->styleQueries->getIdByName($this->styleA->name, $this->companyId);
    $this->assertEquals($this->styleA->id, $response);
});

test('getStylesExport method returns style as expected', function (): void {
    $response = $this->styleQueries->getStylesExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->styleA->id)
        ->toHaveKey('name', $this->styleA->name);
});

test('styles can be searched by name', function (): void {
    $company = Company::factory()->create();

    $style = Style::factory()->create([
        'company_id' => $company->id,
        'name' => 'my_style',
    ]);

    $response = $this->styleQueries->getFilteredStylesByCompanyId('my_style', $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $style->id)
        ->toHaveKey('name', $style->name);
});

test(
    'getCachedStylesSalesForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
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

        $style = Style::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $product = Product::factory()->create([
            'style_id' => $style->id,
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
            'happened_at' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget('cache-style-sales-' . $location->id . now()->format('Y-m-d'));

        $response = $this->styleQueries->getCachedStylesSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $style->id)
            ->toHaveKey('name', $style->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-style-sales-' . $location->id . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->styleQueries->getCachedStylesSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedStylesSalesForChart method returns result as expected with brand selection',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
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

        $style = Style::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'style_id' => $style->id,
            'brand_id' => $brandId,
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

        Cache::forget('cache-style-sales-' . $location->id . $brandId . now()->format('Y-m-d'));

        $response = $this->styleQueries->getCachedStylesSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $style->id)
            ->toHaveKey('name', $style->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-style-sales-' . $location->id . $brandId . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->styleQueries->getCachedStylesSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);

        $cachedResponse = $this->styleQueries->getCachedStylesSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->not->toBe($response);
    }
);

test('it returns the sales summary for a style within a specific category and date', function (): void {
    $categoryId = Category::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'style_id' => $this->styleA->id,
    ]);

    $product->categories()->attach([$categoryId]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->styleQueries->getStyleSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a style within a specific brand and date', function (): void {
    $brandId = Brand::factory()->create([
        'name' => 'test',
    ])->id;

    $this->company->brands()->attach($brandId);

    $product = Product::factory()->create([
        'style_id' => $this->styleA->id,
        'brand_id' => $brandId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->styleQueries->getStyleSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a style within a specific color and date', function (): void {
    $colorId = Color::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'style_id' => $this->styleA->id,
        'color_id' => $colorId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->styleQueries->getStyleSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a style within a specific color group and date', function (): void {
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
        'style_id' => $this->styleA->id,
        'color_id' => $colorId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->styleQueries->getStyleSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a style within a specific department and date', function (): void {
    $departmentId = Department::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'style_id' => $this->styleA->id,
        'department_id' => $departmentId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->styleQueries->getStyleSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a style within a specific size and date', function (): void {
    $sizeId = Size::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'style_id' => $this->styleA->id,
        'size_id' => $sizeId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->styleQueries->getStyleSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it retrieves a collection of styles by their IDs for a specific company', function (): void {
    $styleId = Style::factory()->create([
        'company_id' => $this->companyId,
    ])->id;

    $response = $this->styleQueries->getByIds([$styleId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test('doAllStylesExist method return true when all style ids exists with company', function (): void {
    $styleId = $this->styleA->id;
    $response = $this->styleQueries->doAllStylesExist($this->companyId, [$styleId]);
    $this->assertTrue($response);
});

test(
    'getCachedSeasonalTopFiveStyleSalesForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
        ]);

        $date = now();

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counter->id,
            'opened_by_pos_at' => $date->format('Y-m-d H:i:s'),
        ]);

        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
            'happened_at' => $date->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        $style = Style::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $product = Product::factory()->create([
            'style_id' => $style->id,
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
            'happened_at' => $date->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget('cache-seasonal-style-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d'));

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => null,
            'location_id' => $location->id,
        ];

        $response = $this->styleQueries->getCachedSeasonalTopFiveStyleSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $style->id)
            ->toHaveKey('name', $style->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has('cache-seasonal-style-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d'))
        )->toBeTrue();

        $cachedResponse = $this->styleQueries->getCachedSeasonalTopFiveStyleSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test('getStyleNamesByIds method returns proper response', function (): void {
    $response = $this->styleQueries->getStyleNamesByIds($this->companyId, [$this->styleA->id]);
    expect($response->toArray())
        ->toHaveKey('names', $this->styleA->name);
});

test('Get Style name for export PDF headers', function (): void {
    $response = $this->styleQueries->getStyleNameForFilter([$this->styleA->id]);

    $this->assertIsString($response);
});

test('getAllByCompanyId returns the Styles details', function (): void {
    $this->styleB->delete();

    $response = $this->styleQueries->getAllByCompanyId($this->companyId);

    expect($response->count())->toBe(1);
    expect($response->toArray()[0])->toHaveKey('id', $this->styleA->id);
});

test('getByOnlyId returns the Style detail by id', function (): void {
    $response = $this->styleQueries->getByOnlyId($this->styleA->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->styleA->id)
        ->toHaveKey('name', $this->styleA->name)
        ->toHaveKey('code', $this->styleA->code)
        ->toHaveKey('company_id', $this->styleA->company_id);
});
