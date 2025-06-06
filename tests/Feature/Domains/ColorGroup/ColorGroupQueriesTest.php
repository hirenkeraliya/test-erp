<?php

declare(strict_types=1);

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\DataObjects\ColorGroupData;
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

    $this->colorGroupA = ColorGroup::factory()->create([
        'company_id' => $this->companyId,
        'code' => 'JKL',
    ]);

    $this->colorGroupB = ColorGroup::factory()->create([
        'company_id' => $this->companyId,
        'code' => 'XYZ',
    ]);

    $this->colorGroupQueries = new ColorGroupQueries();
});

test('Color group can be searched', function (): void {
    $response = $this->colorGroupQueries->listQuery([
        'search_text' => $this->colorGroupA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name);
});

test("Color groups are returned as per admin's company", function (): void {
    ColorGroup::factory()->create();

    $response = $this->colorGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->colorGroupB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name);
});

test('new color groups can be added', function (): void {
    $this->colorGroupQueries->addNew(new ColorGroupData('colorName', 'colorCode', '#ABCD'), $this->companyId);

    $this->assertDatabaseHas('color_groups', [
        'name' => 'colorName',
        'code' => 'colorCode',
        'company_id' => $this->companyId,
    ]);
});

test('A color groups can be fetched', function (): void {
    $response = $this->colorGroupQueries->getById($this->colorGroupA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKey('code', $this->colorGroupA->code);
});

test('A color group can be updated', function (): void {
    $this->colorGroupQueries->update(
        new ColorGroupData('colorNameUpdate', 'colorCodeUpdate', '#ABCD'),
        $this->colorGroupA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('color_groups', [
        'name' => 'colorNameUpdate',
        'code' => 'colorCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('getColorGroupsExport method returns color groups as expected', function (): void {
    $response = $this->colorGroupQueries->getColorGroupsExport([
        'search_text' => $this->colorGroupA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->colorGroupA->id)
        ->toHaveKey('name', $this->colorGroupA->name);
});

test(
    'it returns the sales summary for a color group within a specific category and date',
    function (): void {
        $categoryId = Category::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'group_id' => $this->colorGroupA->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        $response = $this->colorGroupQueries->getColorGroupSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a color group within a specific color and date',
    function (): void {
        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'group_id' => $this->colorGroupA->id,
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
            'id' => $color->id,
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

        $response = $this->colorGroupQueries->getColorGroupSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a color group within a specific brand and date',
    function (): void {
        $brandId = Brand::factory()->create([
            'name' => 'test',
        ])->id;

        $this->company->brands()->attach($brandId);

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'group_id' => $this->colorGroupA->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        $response = $this->colorGroupQueries->getColorGroupSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a color group within a specific department and date',
    function (): void {
        $departmentId = Department::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'group_id' => $this->colorGroupA->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        $response = $this->colorGroupQueries->getColorGroupSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a color group within a specific sizes and date',
    function (): void {
        $sizeId = Size::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'group_id' => $this->colorGroupA->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        $response = $this->colorGroupQueries->getColorGroupSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a color group within a specific style and date',
    function (): void {
        $styleId = Style::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'group_id' => $this->colorGroupA->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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

        $response = $this->colorGroupQueries->getColorGroupSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
        ->toHaveKey('name', $this->colorGroupA->name)
        ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'getCachedColorGroupSalesForChart method returns result as expected',
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

        $colorGroup = ColorGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
        ]);

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
            'group_id' => $colorGroup->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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
            'cache-color-groups-sales-' . $this->companyId . '-' . $location->id . '-' . null . '-' . now()->format(
                'Y-m-d'
            )
        );

        $response = $this->colorGroupQueries->getCachedColorGroupSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $colorGroup->id)
            ->toHaveKey('name', $colorGroup->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has(
                'cache-color-groups-sales-' . $this->companyId . '-' . $location->id . '-' . null . '-' . now()->format(
                    'Y-m-d'
                )
            )
        )->toBeTrue();

        $cachedResponse = $this->colorGroupQueries->getCachedColorGroupSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedColorGroupSalesForChart method returns result as expected with brand Selection',
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

        $colorGroup = ColorGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
        ]);

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
            'group_id' => $colorGroup->id,
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'my_size',
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

        Cache::forget(
            'cache-color-groups-sales-' . $this->companyId . '-' . $location->id . '-' . $brandId . '-' . now()->format(
                'Y-m-d'
            ) . $brandId
        );

        $response = $this->colorGroupQueries->getCachedColorGroupSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $colorGroup->id)
            ->toHaveKey('name', $colorGroup->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has(
                'cache-color-groups-sales-' . $this->companyId . '-' . $location->id . '-' . $brandId . '-' . now()->format(
                    'Y-m-d'
                ) . $brandId
            )
        )->toBeTrue();

        $cachedResponse = $this->colorGroupQueries->getCachedColorGroupSalesForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);

        $cachedResponse = $this->colorGroupQueries->getCachedColorGroupSalesForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->not->toBe($response);
    }
);

test(
    'getCachedSeasonalTopFiveColorGroupSalesForChart method returns result as expected',
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

        $colorGroup = ColorGroup::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
        ]);

        $color = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_size',
            'group_id' => $colorGroup->id,
        ]);

        $product = Product::factory()->create([
            'color_id' => $color->id,
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
            'cache-seasonal-color-groups-sales-' . $this->companyId . '-' . $location->id . '-' . null . '-' . $date->format(
                'Y-m-d'
            ) . $date->format('Y-m-d')
        );

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => null,
            'location_id' => $location->id,
        ];

        $response = $this->colorGroupQueries->getCachedSeasonalTopFiveColorGroupSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $colorGroup->id)
            ->toHaveKey('name', $colorGroup->name)
            ->toHaveKey('sales_count', 1);

        expect(
            Cache::has(
                'cache-seasonal-color-groups-sales-' . $this->companyId . '-' . $location->id . '-' . null . '-' . $date->format(
                    'Y-m-d'
                ) . $date->format('Y-m-d')
            )
        )->toBeTrue();

        $cachedResponse = $this->colorGroupQueries->getCachedSeasonalTopFiveColorGroupSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test('getColorGroupsByCompanyId method returns color group lists', function (): void {
    $filterData = [
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'after_updated_at' => null,
        'search_text' => '',
    ];

    $response = $this->colorGroupQueries->getColorGroupsByCompanyId($this->companyId, $filterData);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'name', 'code', 'updated_at']);
});

test('existsByName method returns boolean as expected', function (): void {
    $response = $this->colorGroupQueries->existsByName('test', $this->companyId);
    $this->assertFalse($response);

    $response = $this->colorGroupQueries->existsByName($this->colorGroupA->name, $this->companyId);
    $this->assertTrue($response);
});

test('codeTakenByAnotherColorGroup method returns boolean as expected', function (): void {
    $response = $this->colorGroupQueries->codeTakenByAnotherColorGroup(
        $this->colorGroupA->code,
        $this->colorGroupA->name,
        $this->companyId
    );
    $this->assertFalse($response);

    $colorGroup = ColorGroup::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->colorGroupQueries->codeTakenByAnotherColorGroup(
        $colorGroup->code,
        $this->colorGroupA->name,
        $this->companyId
    );
    $this->assertTrue($response);
});

test('A color group can be updated by name', function (): void {
    $this->colorGroupQueries->updateByName(
        [
            'company_id' => $this->companyId,
            'name' => 'tests',
            'code' => '123456',
        ],
        $this->colorGroupA->name,
        $this->companyId
    );

    $this->assertDatabaseHas('color_groups', [
        'company_id' => $this->companyId,
        'name' => 'tests',
        'code' => '123456',
    ]);
});
