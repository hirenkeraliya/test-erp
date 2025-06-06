<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Http\Controllers\Api\WarehouseManager\PackageTypeController;
use App\Models\PackageType;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

test('It Calls getList of PackageTypeQueries class and returns the lists', function (): void {
    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $packageType = PackageType::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use ($packageType): void {
        $mock->shouldReceive('getLists')
            ->once()
            ->andReturn(collect([$packageType]));
    });

    $this->mock(EmployeeQueries::class, function ($mock) use ($warehouseManager): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($warehouseManager->employee_id);
    });

    $packageTypeController = new PackageTypeController($packageTypeQueries);
    $response = $packageTypeController->getList($request);

    expect($response['package_type']->first()->toArray())->toBe($packageType->toArray());
});
