<?php

declare(strict_types=1);

use App\Domains\Color\ColorQueries;
use App\Domains\Color\DataObjects\ColorData;
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
use App\Models\Size;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->colorA = Color::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
    ]);

    $this->colorB = Color::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
    ]);

    $this->colorQueries = new ColorQueries();
});

test('Color can be searched', function (): void {
    $response = $this->colorQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->colorA->name);
});

test("Colors are returned as per admin's company", function (): void {
    Color::factory()->create();

    $response = $this->colorQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->colorB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->colorA->name);
});

test('new colors can be added', function (): void {
    $this->colorQueries->addNew(new ColorData('colorName', 'colorCode', '#ABCDE'), $this->companyId);

    $this->assertDatabaseHas('colors', [
        'name' => 'colorName',
        'code' => 'colorCode',
        'company_id' => $this->companyId,
    ]);
});

test('A colors can be fetched', function (): void {
    $response = $this->colorQueries->getById($this->colorA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->colorA->name)
        ->toHaveKey('code', $this->colorA->code);
});

test('A color can be updated', function (): void {
    $this->colorQueries->update(
        new ColorData('colorNameUpdate', 'colorCodeUpdate', '#ABCDE'),
        $this->colorA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('colors', [
        'name' => 'colorNameUpdate',
        'code' => 'colorCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('colors are returned as per page', function (): void {
    $response = $this->colorQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->colorB->name);
});

test('Colors can be sorted by id', function (): void {
    $response = $this->colorQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->colorA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->colorB->name);
});

test('colors can be fetched', function (): void {
    $response = $this->colorQueries->getWithBasicColumns($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->colorA->id)
        ->toHaveKey('name', $this->colorA->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->colorQueries->existsByName($this->colorA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->colorQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns the color details', function (): void {
    $response = $this->colorQueries->getIdByName($this->colorA->name, $this->companyId);
    $this->assertEquals($this->colorA->id, $response);
});

test('colors can be searched by name', function (): void {
    $company = Company::factory()->create();

    $color = Color::factory()->create([
        'company_id' => $company->id,
        'name' => 'my_color',
    ]);

    $response = $this->colorQueries->getFilteredColorsByCompanyId('my_color', $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $color->id)
        ->toHaveKey('name', $color->name);
});

test('getColorsExport method returns colors as expected', function (): void {
    $response = $this->colorQueries->getColorsExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->colorA->id)
        ->toHaveKey('name', $this->colorA->name);
});

test(
    'getCachedColorsSalesForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
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

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_color',
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        Cache::forget('cache-colors-sales-' . $location->id . now()->format('Y-m-d'));

        $response = $this->colorQueries->getCachedColorsSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $color->id)
            ->toHaveKey('name', $color->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-colors-sales-' . $location->id . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->colorQueries->getCachedColorsSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedColorsSalesForChart method returns result as expected while brand is selected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
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

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_color',
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'my_brand',
        ])->id;

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        Cache::forget('cache-colors-sales-' . $location->id . $brandId . now()->format('Y-m-d'));

        $response = $this->colorQueries->getCachedColorsSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $color->id)
            ->toHaveKey('name', $color->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-colors-sales-' . $location->id . $brandId . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->colorQueries->getCachedColorsSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toBe($response);

        $cachedResponse = $this->colorQueries->getCachedColorsSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->not->toBe($response);
    }
);

test('it returns the sales summary for a color within a specific category and date', function (): void {
    $categoryId = Category::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $this->colorA->id,
    ]);

    $product->categories()->attach([$categoryId]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->colorQueries->getColorSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a color within a specific brand and date', function (): void {
    $brandId = Brand::factory()->create([
        'name' => 'test',
    ])->id;

    $this->company->brands()->attach($brandId);

    $product = Product::factory()->create([
        'color_id' => $this->colorA->id,
        'brand_id' => $brandId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->colorQueries->getColorSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a color within a specific department and date', function (): void {
    $departmentId = Department::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $this->colorA->id,
        'department_id' => $departmentId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->colorQueries->getColorSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a color within a specific color group and date', function (): void {
    $colorGroupId = ColorGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $color = Color::factory()->create([
        'company_id' => $this->companyId,
        'group_id' => $colorGroupId,
    ]);

    $product = Product::factory()->create([
        'color_id' => $color->id,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->colorQueries->getColorSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $color->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a color within a specific size and date', function (): void {
    $sizeId = Size::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $this->colorA->id,
        'size_id' => $sizeId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->colorQueries->getColorSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a color within a specific style and date', function (): void {
    $styleId = Style::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'color_id' => $this->colorA->id,
        'style_id' => $styleId,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
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

    $response = $this->colorQueries->getColorSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('getCachedTopSellingColor method returns the sold and returns of sales colors with cache', function (): void {
    $date = now()->format('Y-m-d');

    $product = Product::factory()->create([
        'color_id' => $this->colorA->id,
        'company_id' => $this->companyId,
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->brands()->sync($product->brand_id);

    $counterId = Counter::factory()->create([
        'location_id' => $location->id,
    ])->id;

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
        'opened_by_pos_at' => Carbon::now(),
    ]);

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

    $response = $this->colorQueries->getCachedTopSellingColor($this->companyId, $location->id, null, $date, $date);

    expect($response)->toBeCollection();

    expect($response->first()->toArray())->toHaveKey('name', $this->colorA->name)
        ->toHaveKeys(['total_sales', 'total_units_sold']);
});

test(
    'getCachedSeasonalTopFiveColorsSalesForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
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

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_color',
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget('cache-seasonal-colors-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d'));

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => null,
            'location_id' => $location->id,
        ];

        $response = $this->colorQueries->getCachedSeasonalTopFiveColorsSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $color->id)
            ->toHaveKey('name', $color->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has('cache-seasonal-colors-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d'))
        )->toBeTrue();

        $cachedResponse = $this->colorQueries->getCachedSeasonalTopFiveColorsSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedWeekDistributionColorForChart method returns result as expected',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counter = Counter::factory()->create([
            'location_id' => $location->id,
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

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_color',
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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
            'happened_at' => $date->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget(
            'cache-seasonal-week-based-colors-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d')
        );

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => null,
            'location_id' => $location->id,
        ];

        $response = $this->colorQueries->getCachedWeekDistributionColorForChart($filterData, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $color->id)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has(
                'cache-seasonal-week-based-colors-sales-' . $location->id . $date->format('Y-m-d') . $date->format(
                    'Y-m-d'
                )
            )
        )->toBeTrue();

        $cachedResponse = $this->colorQueries->getCachedWeekDistributionColorForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test('getColorNamesByIds method returns proper response', function (): void {
    $response = $this->colorQueries->getColorNamesByIds($this->companyId, [$this->colorA->id]);
    expect($response->toArray())
        ->toHaveKey('names', $this->colorA->name);
});

test('codeTakenByAnotherColor method returns boolean as expected', function (): void {
    $response = $this->colorQueries->codeTakenByAnotherColor(
        $this->colorA->code,
        $this->colorA->name,
        $this->companyId
    );
    $this->assertFalse($response);

    $color = Color::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->colorQueries->codeTakenByAnotherColor($color->code, $this->colorA->name, $this->companyId);
    $this->assertTrue($response);
});

test('A color can be updated by name', function (): void {
    $this->colorQueries->updateByName(
        [
            'company_id' => $this->companyId,
            'name' => 'tests',
            'code' => '123456',
        ],
        $this->colorA->name,
        $this->companyId
    );

    $this->assertDatabaseHas('colors', [
        'company_id' => $this->companyId,
        'name' => 'tests',
        'code' => '123456',
    ]);
});

test('Get Colors name for export PDF headers', function (): void {
    $response = $this->colorQueries->getColorNameForFilter([$this->colorA->id]);

    $this->assertIsString($response);
});

test('firstOrCreate method returns proper response', function (): void {
    $response = $this->colorQueries->firstOrCreate($this->colorA->name, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $this->colorA->id)
        ->toHaveKey('name', $this->colorA->name);

    $response = $this->colorQueries->firstOrCreate('Test Color', $this->companyId);

    expect($response->toArray()['id'])->not->toBe($this->colorA->id);
});
