<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Promoter\DataObjects\PromoterApplicationData;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Store\DataObjects\PromoterStoreData;
use App\Http\Controllers\Api\Promoter\PromoterController;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the updateProfile method of the EmployeeQueries class', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $employeeRecords = new PromoterApplicationData(
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

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('updateUsername')
            ->once();
    });

    $promoterController = new PromoterController();
    $promoterController->updateProfile($employeeRecords, $request);
});

test('calls the getStoreStock method and returns stores stock record', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $filteredData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
    ];

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $promoterStoreData = new PromoterStoreData(...$filteredData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoryStocksForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $promoterController = new PromoterController();
    $response = $promoterController->getStoreStock($request, $promoterStoreData, 1);

    expect($response['store_stock']);
});

test('It calls the getProfileDetails method to fetch profile data', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
        $mock->shouldReceive('getByIdWithEmployeeAndLocations')
            ->andReturn($promoter)
            ->once();
    });

    $promoterController = new PromoterController();
    $response = $promoterController->getProfileDetails($request);

    $this->assertEquals($promoter->username, $response['promoter_details']->username);
});
