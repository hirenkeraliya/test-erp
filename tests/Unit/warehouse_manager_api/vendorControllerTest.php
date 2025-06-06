<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Vendor\DataObjects\VendorListForWarehouseManagerAppData;
use App\Domains\Vendor\VendorQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Api\WarehouseManager\VendorController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vendor;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);
});

test('It calls the getVendors method and return vendors list', function (): void {
    $vendor = Vendor::factory()->make([
        'company_id' => $this->company->id,
    ]);

    $vendorData = [
        'warehouse_id' => 1,
        'location_id' => 1,
        'search_text' => null,
    ];

    $vendorListForWarehouseManagerAppData = new VendorListForWarehouseManagerAppData(...$vendorData);

    $this->mock(WarehouseManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndWarehouseId')
            ->once()
            ->with((int) $this->warehouseManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfWarehouse')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(VendorQueries::class, function ($mock) use ($vendor): void {
        $mock->shouldReceive('getVendorByCompanyId')
            ->with($this->company->id, null)
            ->once()
            ->andReturn(collect([$vendor]));
    });

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

    $vendorController = new VendorController();
    $response = $vendorController->getVendors($request, $vendorListForWarehouseManagerAppData);

    expect($response['vendors']->collection)->toBeInstanceOf(Collection::class);
});

test(
    'getVendors method throws an Exception when the warehouse manager specify a different warehouse',
    function (): void {
        $vendorData = [
            'warehouse_id' => 1,
            'location_id' => 1,
            'search_text' => null,
        ];

        $vendorListForWarehouseManagerAppData = new VendorListForWarehouseManagerAppData(...$vendorData);

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
                ->once()
                ->with((int) $this->warehouseManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $vendorController = new VendorController();
        $vendorController->getVendors($request, $vendorListForWarehouseManagerAppData);
    }
)->throws(HttpException::class);
