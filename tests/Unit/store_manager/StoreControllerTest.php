<?php

declare(strict_types=1);

use App\Domains\Location\LocationQueries;
use App\Domains\Store\DataObjects\StoreSelectionData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\StoreManager\StoreController;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Illuminate\Http\Request;

test('getAuthorizedStores method returns proper response', function (): void {
    $storeController = new StoreController();

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager->locations = collect([$location]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getStoreManagerStores')
            ->once()
            ->with($storeManager)
            ->andReturn($storeManager->locations);
    });

    $response = $storeController->getAuthorizedStores($request);

    expect($response['locations'][0])
        ->toHaveKey('name', $location->name);
});

test('setSelectedStore method works if location is valid', function (): void {
    Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager->locations = collect([$location]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeSelectionData = new StoreSelectionData($location->id);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getStoreManagerStoresId')
            ->once()
            ->with($storeManager)
            ->andReturn([1]);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->once()
            ->andReturn(1);
    });

    $storeController = new StoreController();
    $redirectResponse = $storeController->setSelectedStore($storeSelectionData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertStringContainsString('store-manager/dashboard', $redirectResponse->getTargetUrl());
});

test('setSelectedStore method throw exception if location is not valid', function (): void {
    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $storeManager->locations = collect([]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeSelectionData = new StoreSelectionData(5);
    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getStoreManagerStoresId')
            ->once()
            ->with($storeManager);
    });

    $storeController = new StoreController();
    $storeController->setSelectedStore($storeSelectionData, $request);
})->throws(RedirectBackWithErrorException::class);
