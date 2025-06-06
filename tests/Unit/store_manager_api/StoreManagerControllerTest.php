<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\StoreManager\DataObjects\StoreManagerApplicationData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\StoreManagerController;
use App\Models\StoreManager;
use Illuminate\Http\Request;

test('it can update store manager profile successfully', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $employeeRecords = new StoreManagerApplicationData(
        username: 'test',
        first_name: 'test',
        last_name: 'test',
        email: 'test@gmail.com',
        mobile_number : '1234567890',
        home_contact: '12345667890',
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

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('updateUsername')
            ->once();
    });

    $promoterController = new StoreManagerController();
    $promoterController->updateProfile($employeeRecords, $request);
});

test('It calls the getProfileDetails method to fetch profile data', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithStores')
            ->andReturn($storeManager)
            ->once();
    });

    $storeManagerController = new StoreManagerController();
    $response = $storeManagerController->getProfileDetails($request);

    $this->assertEquals($storeManager->username, $response['store_manager_details']->username);
});
