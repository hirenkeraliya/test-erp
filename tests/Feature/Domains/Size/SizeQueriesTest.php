<?php

declare(strict_types=1);

use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Size\DataObjects\SizeData;
use App\Domains\Size\SizeQueries;
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

    $this->sizeA = Size::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    $this->sizeB = Size::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'XYZW',
        'code' => 'XYZW',
    ]);

    $this->sizeQueries = new SizeQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('Sizes can be searched', function (): void {
    $response = $this->sizeQueries->listQuery([
        'search_text' => 'AB',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKey('code', $this->sizeA->code);
});

test('Sizes can be sorted by name', function (): void {
    $response = $this->sizeQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKey('code', $this->sizeA->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->sizeB->name)
        ->toHaveKey('code', $this->sizeB->code);
});

test('Sizes are returned as per page', function (): void {
    $response = $this->sizeQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 1,
        'group_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKey('code', $this->sizeA->code);
});

test('A size can be fetched', function (): void {
    $response = $this->sizeQueries->getById($this->sizeA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKey('code', $this->sizeA->code);
});

test('New size can be added', function (): void {
    $this->sizeQueries->addNew(new SizeData('EFGH', 'EFGH', 1), $this->companyId);

    $this->assertDatabaseHas('sizes', [
        'company_id' => $this->companyId,
        'name' => 'EFGH',
        'code' => 'EFGH',
    ]);
});

test('A size can be updated', function (): void {
    $this->sizeQueries->update(new SizeData('IJKL', 'IJKL', 2), $this->sizeA->id, $this->companyId);

    $this->assertDatabaseHas('sizes', [
        'company_id' => $this->companyId,
        'name' => 'IJKL',
        'code' => 'IJKL',
    ]);
});

test('sizes can be fetched', function (): void {
    $response = $this->sizeQueries->getWithBasicColumns($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->sizeA->id)
        ->toHaveKey('name', $this->sizeA->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->sizeQueries->existsByName($this->sizeA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->sizeQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns size details', function (): void {
    $response = $this->sizeQueries->getIdByName($this->sizeA->name, $this->companyId);
    $this->assertEquals($this->sizeA->id, $response);
});

test('getSizesExport method returns sizes as expected', function (): void {
    $response = $this->sizeQueries->getSizesExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'group_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->sizeA->id)
        ->toHaveKey('name', $this->sizeA->name);
});

test('it returns the sales summary for a size within a specific category and date', function (): void {
    $categoryId = Category::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'size_id' => $this->sizeA->id,
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

    $response = $this->sizeQueries->getSizeSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a size within a specific color and date', function (): void {
    $colorId = Color::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'size_id' => $this->sizeA->id,
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

    $response = $this->sizeQueries->getSizeSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a size within a specific brand and date', function (): void {
    $brandId = Brand::factory()->create([
        'name' => 'test',
    ])->id;

    $this->company->brands()->attach($brandId);

    $product = Product::factory()->create([
        'size_id' => $this->sizeA->id,
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

    $response = $this->sizeQueries->getSizeSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a size within a specific color group and date', function (): void {
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
        'size_id' => $this->sizeA->id,
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

    $response = $this->sizeQueries->getSizeSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a size within a specific style and date', function (): void {
    $styleId = Style::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'size_id' => $this->sizeA->id,
        'style_id' => $styleId,
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

    $response = $this->sizeQueries->getSizeSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test('it returns the sales summary for a size within a specific department and date', function (): void {
    $departmentId = Department::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'test',
    ])->id;

    $product = Product::factory()->create([
        'size_id' => $this->sizeA->id,
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

    $response = $this->sizeQueries->getSizeSalesSummary($filterData, $this->companyId);

    expect($response)->toBeInstanceOf(Collection::class);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->sizeA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
});

test(
    'getCachedSizeSalesForChart method returns result as expected with brand selection',
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

        $size = Size::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'my_brand',
        ])->id;

        $product = Product::factory()->create([
            'size_id' => $size->id,
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

        Cache::forget(
            'cache-sizes-sales-' . $this->companyId . '-' . $location->id . '-' . $brandId . '-' . now()->format(
                'Y-m-d'
            ) . $brandId
        );

        $response = $this->sizeQueries->getCachedSizeSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $size->id)
            ->toHaveKey('name', $size->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has(
                'cache-sizes-sales-' . $this->companyId . '-' . $location->id . '-' . $brandId . '-' . now()->format(
                    'Y-m-d'
                ) . $brandId
            )
        )->toBeTrue();

        $cachedResponse = $this->sizeQueries->getCachedSizeSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);

        $cachedResponse = $this->sizeQueries->getCachedSizeSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->not->toBe($response);
    }
);

test(
    'getCachedSizeSalesForChart method returns result as expected',
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

        $size = Size::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
        ]);

        $product = Product::factory()->create([
            'size_id' => $size->id,
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

        Cache::forget(
            'cache-sizes-sales-' . $this->companyId . '-' . $location->id . '-' . null . '-' . now()->format('Y-m-d')
        );

        $response = $this->sizeQueries->getCachedSizeSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $size->id)
            ->toHaveKey('name', $size->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has('cache-sizes-sales-' . $this->companyId . '-' . $location->id . '-' . null . '-' . now()->format(
                'Y-m-d'
            ))
        )->toBeTrue();

        $cachedResponse = $this->sizeQueries->getCachedSizeSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedSeasonalTopFiveSizeSalesForChart method returns result as expected with brand selection',
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

        $size = Size::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'my_brand',
        ])->id;

        $product = Product::factory()->create([
            'size_id' => $size->id,
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
            'happened_at' => $date->endOfDay()->format('Y-m-d H:i:s'),
        ]);

        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.00,
            'total_price_paid' => 10.00,
        ]);

        Cache::forget(
            'cache-seasonal-sizes-sales-' . $this->companyId . '-' . $location->id . '-' . $brandId . '-' . $date->format(
                'Y-m-d'
            ) . $date->format('Y-m-d')
        );

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => $brandId,
            'location_id' => $location->id,
        ];

        $response = $this->sizeQueries->getCachedSeasonalTopFiveSizeSalesForChart($filterData, $this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $size->id)
            ->toHaveKey('name', $size->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has(
                'cache-seasonal-sizes-sales-' . $this->companyId . '-' . $location->id . '-' . $brandId . '-' . $date->format(
                    'Y-m-d'
                ) . $date->format('Y-m-d')
            )
        )->toBeTrue();

        $cachedResponse = $this->sizeQueries->getCachedSeasonalTopFiveSizeSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test('codeTakenByAnotherSize method returns boolean as expected', function (): void {
    $response = $this->sizeQueries->codeTakenByAnotherSize(
        $this->sizeA->code,
        $this->sizeA->name,
        $this->companyId
    );
    $this->assertFalse($response);

    $size = Size::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->sizeQueries->codeTakenByAnotherSize($size->code, $this->sizeA->name, $this->companyId);
    $this->assertTrue($response);
});

test('A size can be updated by name', function (): void {
    $this->sizeQueries->updateByName(
        [
            'company_id' => $this->companyId,
            'name' => 'tests',
            'code' => '123456',
        ],
        $this->sizeA->name,
        $this->companyId
    );

    $this->assertDatabaseHas('sizes', [
        'company_id' => $this->companyId,
        'name' => 'tests',
        'code' => '123456',
    ]);
});

test('Size record returns by id', function (): void {
    $response = $this->sizeQueries->getByOnlyId($this->sizeA->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'name', 'code', 'group_id', 'sort_order', 'company_id']);
});

test('Get Size name for export PDF headers', function (): void {
    $response = $this->sizeQueries->getSizeNameForFilter([$this->sizeA->id]);

    $this->assertIsString($response);
});

test('firstOrCreate method returns proper response', function (): void {
    $response = $this->sizeQueries->firstOrCreate($this->sizeA->name, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $this->sizeA->id)
        ->toHaveKey('name', $this->sizeA->name);

    $response = $this->sizeQueries->firstOrCreate('Test Size', $this->companyId);

    expect($response->toArray()['id'])->not->toBe($this->sizeA->id);
});
