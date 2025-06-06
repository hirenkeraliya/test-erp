<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Vehicle\DataObjects\VehicleData;
use App\Domains\Vehicle\VehicleQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->companyB = Company::factory()->create([
        'code' => '789012',
        'email' => 'companyb@example.com',
    ]);

    $this->admin = Admin::factory()->create();

    $this->vehicle = Vehicle::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Delivery Truck',
        'plate_no' => 'ABC123',
        'type_of_vehicle' => 'Heavy Duty Truck',
        'status' => true,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => $this->admin->id,
    ]);
});

test('call listQuery method fetch the vehicles with search', function (): void {
    $filterData = [
        'search_text' => 'Delivery',
        'per_page' => 10,
    ];

    $vehicleQueries = resolve(VehicleQueries::class);
    $response = $vehicleQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->vehicle->name)
        ->toHaveKey('plate_no', $this->vehicle->plate_no)
        ->toHaveKey('type_of_vehicle', $this->vehicle->type_of_vehicle);
});

test('call listQuery method returns only vehicles from specified company', function (): void {
    Vehicle::factory()->create([
        'company_id' => $this->companyB->id,
        'name' => 'Company B Van',
        'plate_no' => 'XYZ789',
    ]);

    $filterData = [
        'search_text' => '',
        'per_page' => 10,
    ];

    $vehicleQueries = resolve(VehicleQueries::class);
    $response = $vehicleQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->name)->toBe($this->vehicle->name);
});

test('call listQuery method search by plate number', function (): void {
    $filterData = [
        'search_text' => 'ABC123',
        'per_page' => 10,
    ];

    $vehicleQueries = resolve(VehicleQueries::class);
    $response = $vehicleQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->plate_no)->toBe('ABC123');
});

test('call listQuery method search by vehicle type', function (): void {
    $filterData = [
        'search_text' => 'Heavy Duty',
        'per_page' => 10,
    ];

    $vehicleQueries = resolve(VehicleQueries::class);
    $response = $vehicleQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->type_of_vehicle)->toBe('Heavy Duty Truck');
});

test('call listQuery method returns no results when search does not match', function (): void {
    $filterData = [
        'search_text' => 'NonExistentVehicle',
        'per_page' => 10,
    ];

    $vehicleQueries = resolve(VehicleQueries::class);
    $response = $vehicleQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(0);
    expect($response->getCollection())->toHaveCount(0);
});

test('call addNew method creates new vehicle', function (): void {
    $vehicleData = new VehicleData(name: 'Cargo Van', plate_no: 'DEF456', type_of_vehicle: 'Van', status: true);

    $vehicleQueries = resolve(VehicleQueries::class);
    $vehicleQueries->addNew($this->admin, $vehicleData, $this->companyA->id);

    $this->assertDatabaseHas('vehicles', [
        'company_id' => $this->companyA->id,
        'name' => 'Cargo Van',
        'plate_no' => 'DEF456',
        'type_of_vehicle' => 'Van',
        'status' => true,
    ]);
});

test('call addNew method creates new vehicle without type_of_vehicle', function (): void {
    $vehicleData = new VehicleData(name: 'Simple Vehicle', plate_no: 'GHI789', type_of_vehicle: null, status: true);

    $vehicleQueries = resolve(VehicleQueries::class);
    $vehicleQueries->addNew($this->admin, $vehicleData, $this->companyA->id);

    $this->assertDatabaseHas('vehicles', [
        'company_id' => $this->companyA->id,
        'name' => 'Simple Vehicle',
        'plate_no' => 'GHI789',
        'type_of_vehicle' => null,
        'status' => true,
    ]);
});

test('call getById method returns vehicle by id and company', function (): void {
    $vehicleQueries = resolve(VehicleQueries::class);
    $response = $vehicleQueries->getById($this->vehicle->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->vehicle->name)
        ->toHaveKey('plate_no', $this->vehicle->plate_no)
        ->toHaveKey('type_of_vehicle', $this->vehicle->type_of_vehicle)
        ->toHaveKey('id', $this->vehicle->id);
});

test('call getById method does not return vehicle from different company', function (): void {
    $vehicleQueries = resolve(VehicleQueries::class);

    expect(fn () => $vehicleQueries->getById($this->vehicle->id, $this->companyB->id))
        ->toThrow(ModelNotFoundException::class);
});

test('call update method updates vehicle information', function (): void {
    $vehicleData = new VehicleData(
        name: 'Updated Truck',
        plate_no: 'ABC123UPDATED',
        type_of_vehicle: 'Light Duty Truck',
        status: true
    );

    $vehicleQueries = resolve(VehicleQueries::class);
    $vehicleQueries->update($vehicleData, $this->vehicle->id, $this->companyA->id);

    $this->assertDatabaseHas('vehicles', [
        'id' => $this->vehicle->id,
        'company_id' => $this->companyA->id,
        'name' => 'Updated Truck',
        'plate_no' => 'ABC123UPDATED',
        'type_of_vehicle' => 'Light Duty Truck',
        'status' => true,
    ]);
});

test('call update method does not update vehicle from different company', function (): void {
    $vehicleData = new VehicleData(
        name: 'Unauthorized Update',
        plate_no: 'HACK123',
        type_of_vehicle: 'Hacker Vehicle',
        status: true
    );

    $vehicleQueries = resolve(VehicleQueries::class);

    expect(fn () => $vehicleQueries->update($vehicleData, $this->vehicle->id, $this->companyB->id))
        ->toThrow(ModelNotFoundException::class);
});

test('call changeStatus method toggles vehicle status', function (): void {
    expect($this->vehicle->status)->toBeTrue();

    $vehicleQueries = resolve(VehicleQueries::class);
    $vehicleQueries->changeStatus($this->vehicle->id, $this->companyA->id);

    $this->assertDatabaseHas('vehicles', [
        'id' => $this->vehicle->id,
        'status' => false,
    ]);
});

test('call changeStatus method does not change status for vehicle from different company', function (): void {
    $vehicleQueries = resolve(VehicleQueries::class);

    expect(fn () => $vehicleQueries->changeStatus($this->vehicle->id, $this->companyB->id))
        ->toThrow(ModelNotFoundException::class);
});

test('vehicles are scoped by company correctly', function (): void {
    Vehicle::factory()->count(3)->create([
        'company_id' => $this->companyA->id,
    ]);
    Vehicle::factory()->count(2)->create([
        'company_id' => $this->companyB->id,
    ]);

    $filterData = [
        'search_text' => '',
        'per_page' => 20,
    ];

    $vehicleQueries = resolve(VehicleQueries::class);
    $responseA = $vehicleQueries->listQuery($filterData, $this->companyA->id);
    $responseB = $vehicleQueries->listQuery($filterData, $this->companyB->id);

    expect($responseA->total())->toBe(4); // 3 new + 1 from beforeEach
    expect($responseB->total())->toBe(2);
});

test('plate_no uniqueness is enforced per company', function (): void {
    Vehicle::factory()->create([
        'company_id' => $this->companyB->id,
        'plate_no' => 'ABC123', // Same plate number as vehicle in companyA
    ]);

    // Should be able to create vehicle with same plate number in different company
    $this->assertDatabaseCount('vehicles', 2);

    $vehiclesWithSamePlate = Vehicle::where('plate_no', 'ABC123')->get();
    expect($vehiclesWithSamePlate)->toHaveCount(2);
    expect($vehiclesWithSamePlate->pluck('company_id')->unique())->toHaveCount(2);
});
