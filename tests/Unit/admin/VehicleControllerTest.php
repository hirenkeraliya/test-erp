<?php

declare(strict_types=1);

use App\Domains\Vehicle\DataObjects\VehicleData;
use App\Domains\Vehicle\Resources\VehicleResource;
use App\Domains\Vehicle\VehicleQueries;
use App\Http\Controllers\Admin\VehicleController;
use App\Models\Admin;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test(
    'It calls the list query method of the vehicle queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'per_page' => 'test',
        ];

        $vehicleQueries = $this->mock(VehicleQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $vehicleController = new VehicleController($vehicleQueries);

        $response = $vehicleController->fetchVehicles(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(VehicleResource::collection(collect([])), $response['data']);
    }
);

test('It calls the addNew method of vehicle queries class', function (): void {
    $vehicleData = new VehicleData(
        name: 'Delivery Van 1',
        plate_no: 'ABC123',
        type_of_vehicle: 'Van',
        status: true
    );
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);
    $this->actingAs($admin);

    $vehicleQueries = $this->mock(VehicleQueries::class, function ($mock) use ($vehicleData, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with(Admin::class, $vehicleData, $companyId);
    });

    $vehicleController = new VehicleController($vehicleQueries);

    $redirectResponse = $vehicleController->store($vehicleData);

    $this->assertEquals('Vehicle added successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the getById method of vehicle queries class', function (): void {
    $vehicleId = 1;
    $companyId = 1;
    $vehicle = Vehicle::factory()->make([
        'id' => $vehicleId,
        'company_id' => $companyId,
        'created_by_id' => 1,
        'created_by_type' => 'ADMIN',
    ]);

    setCompanyIdInSession($companyId);

    $vehicleQueries = $this->mock(VehicleQueries::class, function ($mock) use ($vehicleId, $companyId, $vehicle): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with($vehicleId, $companyId)
            ->andReturn($vehicle);
    });

    $vehicleController = new VehicleController($vehicleQueries);

    $response = $vehicleController->edit($vehicleId);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('vehicle')->etc());
});

test('It calls the update method of vehicle queries class', function (): void {
    $vehicleId = 1;
    $companyId = 1;
    $vehicleData = new VehicleData(
        name: 'Updated Delivery Van',
        plate_no: 'XYZ789',
        type_of_vehicle: 'Large Van',
        status: true
    );

    setCompanyIdInSession($companyId);

    $vehicleQueries = $this->mock(VehicleQueries::class, function ($mock) use (
        $vehicleData,
        $vehicleId,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($vehicleData, $vehicleId, $companyId);
    });

    $vehicleController = new VehicleController($vehicleQueries);

    $redirectResponse = $vehicleController->update($vehicleData, $vehicleId);

    $this->assertEquals('Vehicle updated successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the changeStatus method of vehicle queries class', function (): void {
    $vehicleId = 1;
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'vehicleId' => $vehicleId,
    ];

    $vehicleQueries = $this->mock(VehicleQueries::class, function ($mock) use ($vehicleId, $companyId): void {
        $mock->shouldReceive('changeStatus')
            ->once()
            ->with($vehicleId, $companyId);
    });

    $vehicleController = new VehicleController($vehicleQueries);

    $response = $vehicleController->changeStatus($vehicleId);

    $this->assertNull($response);
});
