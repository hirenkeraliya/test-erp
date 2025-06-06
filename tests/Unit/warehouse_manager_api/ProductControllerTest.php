<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Product\DataObjects\WarehouseManagerApiProductData;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Api\WarehouseManager\ProductController;
use App\Models\Employee;
use App\Models\Product;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getProducts method and returns products record', function (): void {
    $companyId = 1;

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'search_text' => '',
        'warehouse_id' => 1,
        'location_id' => 1,
        'stock_product' => 'all',
    ];

    $request = new Request();

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $warehouseManagerApiProductData = new WarehouseManagerApiProductData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock) use ($employee): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn($employee->id);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getProductsForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator($product, 1, 15));
    });

    $productController = new ProductController();
    $response = $productController->getProducts($request, $warehouseManagerApiProductData);

    expect($response['data']->resource)->toBeCollection();
    expect($response['total_records'])->toBe(1);
});
