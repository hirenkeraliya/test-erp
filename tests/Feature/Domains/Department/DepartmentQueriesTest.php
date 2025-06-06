<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Department\DataObjects\DepartmentData;
use App\Domains\Department\DepartmentQueries;
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

    $this->departmentA = Department::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Department 1',
        'code' => 'Department001',
    ]);
    $this->departmentB = Department::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Department 2',
        'code' => 'Department002',
    ]);

    $this->departmentQueries = new DepartmentQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('Departments can be searched', function (): void {
    $response = $this->departmentQueries->listQuery([
        'search_text' => 'Department 1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->departmentA->name)
        ->toHaveKey('code', $this->departmentA->code);
});

test('Departments can be sorted by name', function (): void {
    $response = $this->departmentQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->departmentA->name)
        ->toHaveKey('code', $this->departmentA->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->departmentB->name)
        ->toHaveKey('code', $this->departmentB->code);
});

test('Departments are returned as per page', function (): void {
    $response = $this->departmentQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->departmentB->name)
        ->toHaveKey('code', $this->departmentB->code);
});

test("Departments are returned as per admin's company", function (): void {
    $response = $this->departmentQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->departmentB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->departmentA->name);
});

test('A department can be fetched', function (): void {
    $response = $this->departmentQueries->getById($this->departmentA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->departmentA->name)
        ->toHaveKey('code', $this->departmentA->code);
});

test('New department can be added', function (): void {
    $this->departmentQueries->addNew(
        new DepartmentData('Department 3', 'Department003', 0.00, 50.00, DiscountTypes::FLAT->value),
        $this->companyId
    );

    $this->assertDatabaseHas('departments', [
        'company_id' => $this->companyId,
        'name' => 'Department 3',
        'code' => 'Department003',
    ]);
});

test('A department can be updated', function (): void {
    $this->departmentQueries->update(
        new DepartmentData('Department 1.1', 'Department001', 0.00, 50.00, DiscountTypes::FLAT->value),
        $this->departmentA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('departments', [
        'company_id' => $this->companyId,
        'name' => 'Department 1.1',
        'code' => 'Department001',
    ]);
});

test('departments can be fetched', function (): void {
    $response = $this->departmentQueries->getWithBasicColumns($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->departmentA->id)
        ->toHaveKey('name', $this->departmentA->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->departmentQueries->existsByName($this->departmentA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->departmentQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns the department details', function (): void {
    $response = $this->departmentQueries->getIdByName($this->departmentA->name, $this->companyId);
    $this->assertEquals($this->departmentA->id, $response);
});

test('departments can be searched by name', function (): void {
    $company = Company::factory()->create();

    $department = Department::factory()->create([
        'company_id' => $company->id,
        'name' => 'my_department',
    ]);

    $response = $this->departmentQueries->getFilteredDepartmentsByCompanyId('my_department', $company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $department->id)
        ->toHaveKey('name', $department->name);
});

test('getDepartmentsExport method returns departments as expected', function (): void {
    $response = $this->departmentQueries->getDepartmentsExport([
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->departmentB->name);
});

test(
    'getCachedDepartmentSaleForChart method returns result as expected',
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

        $department = Department::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_department',
        ]);

        $product = Product::factory()->create([
            'department_id' => $department->id,
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

        Cache::forget('cache-departments-sales-' . $location->id . now()->format('Y-m-d'));

        $response = $this->departmentQueries->getCachedDepartmentSaleForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $department->id)
            ->toHaveKey('name', $department->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-departments-sales-' . $location->id . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->departmentQueries->getCachedDepartmentSaleForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test(
    'getCachedDepartmentSaleForChart method returns result as expected with brand selection',
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

        $department = Department::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_department',
        ]);

        $brandId = Brand::factory()->create([
            'name' => 'my_brand',
        ])->id;

        $product = Product::factory()->create([
            'department_id' => $department->id,
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

        Cache::forget('cache-departments-sales-' . $location->id . $brandId . now()->format('Y-m-d'));

        $response = $this->departmentQueries->getCachedDepartmentSaleForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $department->id)
            ->toHaveKey('name', $department->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(Cache::has('cache-departments-sales-' . $location->id . $brandId . now()->format('Y-m-d')))->toBeTrue();

        $cachedResponse = $this->departmentQueries->getCachedDepartmentSaleForChart(
            $this->companyId,
            $location->id,
            $brandId,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->toEqual($response);

        $cachedResponse = $this->departmentQueries->getCachedDepartmentSaleForChart(
            $this->companyId,
            $location->id,
            null,
            now()->format('Y-m-d')
        );

        expect($cachedResponse)->not->toBe($response);
    }
);

test(
    'it returns the sales summary for a department within a specific category and date',
    function (): void {
        $categoryId = Category::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'department_id' => $this->departmentA->id,
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

        $response = $this->departmentQueries->getDepartmentSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->departmentA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a department within a specific color and date',
    function (): void {
        $colorId = Color::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'department_id' => $this->departmentA->id,
            'color_id' => $colorId,
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

        $response = $this->departmentQueries->getDepartmentSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->departmentA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a department within a specific brand and date',
    function (): void {
        $brandId = Brand::factory()->create([
            'name' => 'test',
        ])->id;

        $this->company->brands()->attach($brandId);

        $product = Product::factory()->create([
            'department_id' => $this->departmentA->id,
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

        $response = $this->departmentQueries->getDepartmentSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->departmentA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a department within a specific color group and date',
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
            'department_id' => $this->departmentA->id,
            'color_id' => $colorId,
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

        $response = $this->departmentQueries->getDepartmentSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->departmentA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a department within a specific size and date',
    function (): void {
        $sizeId = Size::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'department_id' => $this->departmentA->id,
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

        $response = $this->departmentQueries->getDepartmentSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->departmentA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test(
    'it returns the sales summary for a department within a specific style and date',
    function (): void {
        $styleId = Style::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'test',
        ])->id;

        $product = Product::factory()->create([
            'department_id' => $this->departmentA->id,
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

        $response = $this->departmentQueries->getDepartmentSalesSummary($filterData, $this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->departmentA->name)
            ->toHaveKeys(['total_units_sold', 'total_units_sold']);
    }
);

test('it retrieves a collection of departments by their IDs', function (): void {
    $departmentId = Department::factory()->create()->id;

    $response = $this->departmentQueries->getByIds([$departmentId]);
    expect($response)->toBeInstanceOf(Collection::class);
});

test('doAllDepartmentExist method return true when all department ids exists with company', function (): void {
    $departmentId = $this->departmentA->id;
    $response = $this->departmentQueries->doAllDepartmentExist($this->companyId, [$departmentId]);
    $this->assertTrue($response);
});

test(
    'getCachedSeasonalTopFiveDepartmentSalesForChart method returns result as expected',
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

        $department = Department::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'my_department',
        ]);

        $product = Product::factory()->create([
            'department_id' => $department->id,
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
            'cache-seasonal-departments-sales-' . $location->id . $date->format('Y-m-d') . $date->format('Y-m-d')
        );

        $filterData = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'brand_id' => null,
            'location_id' => $location->id,
        ];

        $response = $this->departmentQueries->getCachedSeasonalTopFiveDepartmentSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $department->id)
            ->toHaveKey('name', $department->name)
            ->toHaveKey('sales_count', 1)
            ->toHaveKey('total_sales', 10)
            ->toHaveKey('total_units_sold', 5);

        expect(
            Cache::has('cache-seasonal-departments-sales-' . $location->id . $date->format('Y-m-d') . $date->format(
                'Y-m-d'
            ))
        )->toBeTrue();

        $cachedResponse = $this->departmentQueries->getCachedSeasonalTopFiveDepartmentSalesForChart(
            $filterData,
            $this->companyId,
        );

        expect($cachedResponse)->toEqual($response);
    }
);

test('getDepartmentNamesByIds method returns proper response', function (): void {
    $response = $this->departmentQueries->getDepartmentNamesByIds($this->companyId, [$this->departmentA->id]);
    expect($response->toArray())
        ->toHaveKey('names', $this->departmentA->name);
});

test('Get Department name for export PDF headers', function (): void {
    $response = $this->departmentQueries->getDepartmentNameForFilter([$this->departmentA->id]);

    $this->assertIsString($response);
});
