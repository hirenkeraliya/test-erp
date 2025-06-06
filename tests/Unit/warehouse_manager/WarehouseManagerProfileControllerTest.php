<?php

declare(strict_types=1);

use App\Domains\WarehouseManager\DataObjects\WarehouseManagerProfileData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\WarehouseManager\WarehouseManagerProfileController;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

it('editProfile method renders the correct view with warehouse manager data', function (): void {
    $warehouseManagerId = 1;
    $warehouseManagerData = new WarehouseManager([
        'username' => 'JohnDoe',
        'employee_id' => 123,
    ]);

    Auth::shouldReceive('id')->andReturn($warehouseManagerId);

    $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $warehouseManagerId,
        $warehouseManagerData
    ): void {
        $mock->shouldReceive('getWarehouseManagerData')
            ->with($warehouseManagerId)
            ->andReturn($warehouseManagerData);
    });

    $warehouseManagerProfileController = new WarehouseManagerProfileController();
    $response = $warehouseManagerProfileController->editProfile();

    $response->rootView('warehouse_manager.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('warehouseManager'));
});

it('updateProfile method handles successful profile update', function (): void {
    $warehouseManagerId = 1;
    $warehouseManagerData = new WarehouseManagerProfileData(1, 'UpdatedUsername', null, null);
    $warehouseManagerDataArray = $warehouseManagerData->all();

    $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $warehouseManagerId,
        $warehouseManagerDataArray
    ): void {
        $mock->shouldReceive('updateWarehouseManagerProfile')
            ->with($warehouseManagerId, $warehouseManagerDataArray)
            ->once();
    });

    $adminProfileController = new WarehouseManagerProfileController();
    $redirectResponse = $adminProfileController->updateProfile($warehouseManagerData, $warehouseManagerId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Warehouse Manager updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('warehouse-manager/dashboard', $redirectResponse->getTargetUrl());
});
