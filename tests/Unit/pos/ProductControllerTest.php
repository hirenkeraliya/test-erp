<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\DataObjects\ProductListDataForPos;
use App\Domains\Product\DataObjects\ProductStockForAllStoreDataForPos;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Api\Pos\ProductController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getList method and returns product records', function (): void {
    $companyId = 1;

    $productListData = [
        'page' => 1,
        'per_page' => 1,
        'search_text' => '',
        'after_updated_at' => null,
    ];

    $productListDataForPos = new ProductListDataForPos(...$productListData);

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];
    $cashier->counter_update_id = 1;

    $filteredData = [
        'per_page' => $productListDataForPos->per_page,
        'page' => $productListDataForPos->page,
        'search_text' => $productListDataForPos->search_text,
        'after_updated_at' => $productListDataForPos->after_updated_at,
    ];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(ProductQueries::class, function ($mock) use (
        $companyId,
        $product,
        $filteredData,
        $location
    ): void {
        $mock->shouldReceive('getList')
            ->once()
            ->with($filteredData, $companyId, $location->id)
            ->andReturn(new LengthAwarePaginator([$product], 20, 15));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn($location);
    });

    $productController = new ProductController();
    $response = $productController->getList($request, $productListDataForPos);

    expect($response)->toBeArray();
});

test('it calls the getProductStockForAllStores method and returns inventories', function (): void {
    $productStockForAllStoreData = [
        'product_id' => 1,
        'after_updated_at' => null,
    ];
    $productStockForAllStoreDataForPos = new ProductStockForAllStoreDataForPos(...$productStockForAllStoreData);

    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
        'username' => 'Cashier',
    ]);

    $cashier->employee = $employee;

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($companyId, $cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn($companyId);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoryByProductIdWithLocation')
            ->once()
            ->andReturn(new Collection([]));
    });

    $productController = new ProductController();
    $response = $productController->getProductStockForAllStores($request, $productStockForAllStoreDataForPos);

    expect($response['product_stocks']->resource)->toBeCollection();
});

test(
    'getProductStockForAllStores method throws an Exception when the cashier not have counter_update_id',
    function (): void {
        $productStockForAllStoreData = [
            'product_id' => 1,
            'after_updated_at' => null,
        ];
        $productStockForAllStoreDataForPos = new ProductStockForAllStoreDataForPos(...$productStockForAllStoreData);
        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];
        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $productController = new ProductController();
        $productController->getProductStockForAllStores($request, $productStockForAllStoreDataForPos);
    }
)->throws(HttpException::class);
