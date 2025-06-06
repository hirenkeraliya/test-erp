<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Api\StoreManager\SaleController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->locationId = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->sale = Sale::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'total_price_paid' => 100,
    ]);
});

test('calls the getSaleDetails method with type SALE and returns sale details record', function (): void {
    $locationId = $this->locationId;
    $sale = $this->sale;

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($locationId, $sale): void {
        $mock->shouldReceive('getSaleItemsForStoreManagerApi')
        ->once()
        ->with(1, $locationId, $this->company->id)
        ->andReturn($sale);
    });

    $saleController = new SaleController($saleQueries);
    $response = $saleController->getSaleDetails($request, $this->sale->id, ModelMapping::SALE->name, $locationId);

    expect($response['sale_details']->resource->toArray())
    ->toHaveKeys(['id', 'total_price_paid']);
});

test(
    'calls the getSaleDetails method with type SALE_RETURN and returns sale details record',
    function (): void {
        $locationId = $this->locationId;
        $sale = $this->sale;

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => $sale->id,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $saleReturnQueries = $this->mock(SaleReturnQueries::class, function ($mock) use (
            $locationId,
            $saleReturn
        ): void {
            $mock->shouldReceive('getSaleReturnItemsForStoreManagerApi')
            ->once()
                ->with($saleReturn->id, $locationId, $this->company->id)
                ->andReturn($saleReturn);
        });

        $saleController = new SaleController($saleReturnQueries);
        $response = $saleController->getSaleDetails(
            $request,
            $this->sale->id,
            ModelMapping::SALE_RETURN->name,
            $locationId
        );

        expect($response['sale_return_details']->resource->toArray())
            ->toHaveKeys(['offline_sale_return_id', 'original_sale_id', 'total_price_paid']);
    }
);

test(
    'getSaleDetails method throws an Exception when pass type rather than SALE or SALE_RETURN',
    function (): void {
        $locationId = $this->locationId;

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $saleController = new SaleController();
        $saleController->getSaleDetails($request, $this->sale->id, 'other', $locationId);
    }
)->throws(HttpException::class);
