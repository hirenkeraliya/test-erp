<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\DataObjects\StoreManagerApiProductData;
use App\Domains\Product\DataObjects\StoreManagerApiUpdateProductPriceData;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Api\StoreManager\ProductController;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getProducts method and returns products record', function (): void {
    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'search_text' => '',
        'store_id' => 1,
        'stock_product' => 'all',
        'location_id' => 1,
    ];

    $request = new Request($filterData);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiProductData = new StoreManagerApiProductData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductsForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator($product, 1, 15));
    });

    $productController = new ProductController();
    $response = $productController->getProducts($request, $storeManagerApiProductData);

    expect($response['data']->resource)->toBeCollection();
    expect($response['total_records'])->toBe(1);
});

test('calls the getProductDetails method and returns products details record', function (): void {
    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $inventory = Inventory::factory()->make([
        'product_id' => 1,
        'location_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductDetailsForApplication')
            ->once()
            ->andReturn($product);
    });

    $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
        $mock->shouldReceive('getInventoriesByProductIds')
            ->once()
            ->andReturn(collect([$inventory]));
    });

    $productController = new ProductController();
    $response = $productController->getProductDetails($request, $product->id, $location->id);

    expect($response['product_details']->resource)->toBeInstanceOf(Product::class);
    expect($response['stock'])->toBe(CommonFunctions::truncateDecimal($inventory->stock));
});

test('It calls the updateProductPrices method of the product Queries class', function (): void {
    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $pricesData = new StoreManagerApiUpdateProductPriceData(
        type_id: '1',
        retail_price: 1.0,
        franchise_price_1: 1.0,
        franchise_price_2: 1.0,
        franchise_price_3: 1.0,
        wholesale_price: 1.0,
        company_or_tender_price: 1.0,
        branch_price: 1.0,
        minimum_price: 1.0,
        original_capital_price: 1.0,
        capital_price: 1.0,
        staff_price: 1.0,
        purchase_cost: 1.0,
        online_price: 1.0,
    );

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('updateProductPrices')
            ->once();
    });

    $productController = new ProductController();
    $productController->updateProductPrices($request, $product->id, $pricesData);
});
