<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\Vendor\DataObjects\VendorListForStoreManagerAppData;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Api\StoreManager\VendorController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\Vendor;
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

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
});

test('It calls the getVendors method and return vendors list', function (): void {
    $vendor = Vendor::factory()->make([
        'company_id' => $this->company->id,
    ]);

    $vendorData = [
        'store_id' => 1,
        'location_id' => 1,
    ];

    $VendorListForStoreManagerAppData = new VendorListForStoreManagerAppData(...$vendorData);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
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
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $venderController = new VendorController();
    $response = $venderController->getVendors($request, $VendorListForStoreManagerAppData);

    expect($response['vendors']->collection)->toBeInstanceOf(Collection::class);
});

test('getVendors method throws an Exception when the store manager specify a different store', function (): void {
    $vendorData = [
        'store_id' => 1,
        'location_id' => 1,
    ];

    $VendorListForStoreManagerAppData = new VendorListForStoreManagerAppData(...$vendorData);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(false);
    });

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $venderController = new VendorController();
    $venderController->getVendors($request, $VendorListForStoreManagerAppData);
})->throws(HttpException::class);
