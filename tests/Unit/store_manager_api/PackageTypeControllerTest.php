<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Http\Controllers\Api\StoreManager\PackageTypeController;
use App\Models\PackageType;
use App\Models\StoreManager;
use Illuminate\Http\Request;

test('It Calls getList of PackageTypeQueries class and returns the lists', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $packageType = PackageType::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use ($packageType): void {
        $mock->shouldReceive('getLists')
            ->once()
            ->andReturn(collect([$packageType]));
    });

    $this->mock(EmployeeQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($storeManager->employee_id);
    });

    $packageTypeController = new PackageTypeController($packageTypeQueries);
    $response = $packageTypeController->getList($request);

    expect($response['package_type']->first()->toArray())->toBe($packageType->toArray());
});
