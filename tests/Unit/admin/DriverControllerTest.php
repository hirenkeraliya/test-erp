<?php

declare(strict_types=1);

use App\Domains\Driver\DataObjects\DriverData;
use App\Domains\Driver\DriverQueries;
use App\Domains\Driver\Resources\DriverResource;
use App\Http\Controllers\Admin\DriverController;
use App\Models\Admin;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test(
    'It calls the list query method of the driver queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'per_page' => 'test',
        ];

        $driverQueries = $this->mock(DriverQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $driverController = new DriverController($driverQueries);

        $response = $driverController->fetchDrivers(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(DriverResource::collection(collect([])), $response['data']);
    }
);

test('It calls the addNew method of driver queries class', function (): void {
    $driverData = new DriverData(
        name: 'John Doe',
        id_number: 'ID123456',
        email: 'john.doe@example.com',
        mobile_number: '1234567890',
        country_code: '+1',
        status: true
    );
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $this->actingAs($admin);

    $driverQueries = $this->mock(DriverQueries::class, function ($mock) use ($driverData, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with(Admin::class, $driverData, $companyId);
    });

    $driverController = new DriverController($driverQueries);

    $redirectResponse = $driverController->store($driverData);

    $this->assertEquals('Driver added successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the getById method of driver queries class', function (): void {
    $driverId = 1;
    $companyId = 1;
    $driver = Driver::factory()->make([
        'id' => $driverId,
        'company_id' => $companyId,
        'created_by_id' => 1,
        'created_by_type' => 'ADMIN',
    ]);

    setCompanyIdInSession($companyId);

    $driverQueries = $this->mock(DriverQueries::class, function ($mock) use ($driverId, $companyId, $driver): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with($driverId, $companyId)
            ->andReturn($driver);
    });

    $driverController = new DriverController($driverQueries);

    $response = $driverController->edit($driverId);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('driver')->etc());
});

test('It calls the update method of driver queries class', function (): void {
    $driverId = 1;
    $companyId = 1;
    $driverData = new DriverData(
        name: 'John Updated',
        id_number: 'ID123456UPDATED',
        email: 'john.updated@example.com',
        mobile_number: '1111222333',
        country_code: '+61',
        status: true
    );

    setCompanyIdInSession($companyId);

    $driverQueries = $this->mock(DriverQueries::class, function ($mock) use ($driverData, $driverId, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($driverData, $driverId, $companyId);
    });

    $driverController = new DriverController($driverQueries);

    $redirectResponse = $driverController->update($driverData, $driverId);

    $this->assertEquals('Driver updated successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the changeStatus method of driver queries class', function (): void {
    $driverId = 1;
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'driverId' => $driverId,
    ];

    $driverQueries = $this->mock(DriverQueries::class, function ($mock) use ($driverId, $companyId): void {
        $mock->shouldReceive('changeStatus')
            ->once()
            ->with($driverId, $companyId);
    });

    $driverController = new DriverController($driverQueries);

    $response = $driverController->changeStatus($driverId);

    $this->assertNull($response);
});
