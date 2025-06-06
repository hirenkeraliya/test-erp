<?php

declare(strict_types=1);

use App\Domains\Employee\DataObjects\WarehouseManagerApplicationData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Api\WarehouseManager\WarehouseManagerController;
use App\Models\Employee;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

test('It calls the updateProfile method of the EmployeeQueries class', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $data = new WarehouseManagerApplicationData(
        username: 'username_test',
        first_name: 'test',
        last_name: 'test',
        email: null,
        mobile_number : '9876543210',
        home_contact: null,
        address_line_1: 'test',
        address_line_2: 'test',
        city: 'test',
        area_code: 'test',
        primary_contact_name: 'test',
        primary_contact_phone: '234567890',
        photo: null,
    );

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('updateProfile')
            ->once();
    });

    $this->mock(WarehouseManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('updateUsername')
            ->once();
    });

    $warehouseManagerController = new WarehouseManagerController();
    $response = $warehouseManagerController->updateProfile($data, $request);

    expect($response['message'])->toContain('Profile Update Successfully!');
});

test('It calls the getProfileDetails method to fetch profile data', function (): void {
    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(WarehouseManagerQueries::class, function ($mock) use ($warehouseManager): void {
        $mock->shouldReceive('getByIdWithWarehouses')
            ->andReturn($warehouseManager)
            ->once();
    });

    $warehouseManagerController = new WarehouseManagerController();
    $response = $warehouseManagerController->getProfileDetails($request);

    $this->assertEquals($warehouseManager->username, $response['warehouse_manager_details']->username);
});
