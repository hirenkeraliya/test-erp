<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Driver\DataObjects\DriverData;
use App\Domains\Driver\DriverQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Driver;
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

    $this->driver = Driver::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'John Doe',
        'id_number' => 'ID123456',
        'email' => 'john.doe@example.com',
        'mobile_number' => '1234567890',
        'country_code' => '+1',
        'status' => true,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => $this->admin->id,
    ]);
});

test('call listQuery method fetch the drivers with search', function (): void {
    $filterData = [
        'search_text' => 'John',
        'per_page' => 10,
    ];

    $driverQueries = resolve(DriverQueries::class);
    $response = $driverQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection())->toHaveCount(1);
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->driver->name)
        ->toHaveKey('id_number', $this->driver->id_number)
        ->toHaveKey('email', $this->driver->email);
});

test('call listQuery method returns only drivers from specified company', function (): void {
    Driver::factory()->create([
        'company_id' => $this->companyB->id,
        'name' => 'Jane Smith',
        'id_number' => 'ID789012',
    ]);

    $filterData = [
        'search_text' => '',
        'per_page' => 10,
    ];

    $driverQueries = resolve(DriverQueries::class);
    $response = $driverQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->name)->toBe($this->driver->name);
});

test('call listQuery method search by id number', function (): void {
    $filterData = [
        'search_text' => 'ID123456',
        'per_page' => 10,
    ];

    $driverQueries = resolve(DriverQueries::class);
    $response = $driverQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->id_number)->toBe('ID123456');
});

test('call listQuery method search by email', function (): void {
    $filterData = [
        'search_text' => 'john.doe@example.com',
        'per_page' => 10,
    ];

    $driverQueries = resolve(DriverQueries::class);
    $response = $driverQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->email)->toBe('john.doe@example.com');
});

test('call listQuery method search by mobile number', function (): void {
    $filterData = [
        'search_text' => '1234567890',
        'per_page' => 10,
    ];

    $driverQueries = resolve(DriverQueries::class);
    $response = $driverQueries->listQuery($filterData, $this->companyA->id);

    expect($response->total())->toBe(1);
    expect($response->getCollection()->first()->mobile_number)->toBe('1234567890');
});

test('call addNew method creates a new driver', function (): void {
    $driverData = new DriverData(
        name: 'Jane Smith',
        id_number: 'ID789012',
        email: 'jane.smith@example.com',
        mobile_number: '9876543210',
        country_code: '+44',
        status: true
    );

    $driverQueries = resolve(DriverQueries::class);
    $driverQueries->addNew($this->admin, $driverData, $this->companyA->id);

    $this->assertDatabaseHas('drivers', [
        'company_id' => $this->companyA->id,
        'name' => 'Jane Smith',
        'id_number' => 'ID789012',
        'email' => 'jane.smith@example.com',
        'mobile_number' => '9876543210',
        'country_code' => '+44',
        'status' => true,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => $this->admin->id,
    ]);
});

test('call addNew method creates driver without email', function (): void {
    $driverData = new DriverData(
        name: 'Bob Wilson',
        id_number: 'ID555777',
        email: null,
        mobile_number: '5555777888',
        country_code: '+91',
        status: true
    );

    $driverQueries = resolve(DriverQueries::class);
    $driverQueries->addNew($this->admin, $driverData, $this->companyA->id);

    $this->assertDatabaseHas('drivers', [
        'company_id' => $this->companyA->id,
        'name' => 'Bob Wilson',
        'id_number' => 'ID555777',
        'email' => null,
        'mobile_number' => '5555777888',
        'country_code' => '+91',
        'status' => true,
    ]);
});

test('call getById method returns driver by id and company', function (): void {
    $driverQueries = resolve(DriverQueries::class);
    $response = $driverQueries->getById($this->driver->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->driver->name)
        ->toHaveKey('id_number', $this->driver->id_number)
        ->toHaveKey('email', $this->driver->email)
        ->toHaveKey('mobile_number', $this->driver->mobile_number)
        ->toHaveKey('country_code', $this->driver->country_code)
        ->toHaveKey('id', $this->driver->id);
});

test('call getById method does not return driver from different company', function (): void {
    $driverQueries = resolve(DriverQueries::class);

    expect(fn () => $driverQueries->getById($this->driver->id, $this->companyB->id))
        ->toThrow(ModelNotFoundException::class);
});

test('call update method updates driver information', function (): void {
    $driverData = new DriverData(
        name: 'John Updated',
        id_number: 'ID123456UPDATED',
        email: 'john.updated@example.com',
        mobile_number: '1111222333',
        country_code: '+61',
        status: true
    );

    $driverQueries = resolve(DriverQueries::class);
    $driverQueries->update($driverData, $this->driver->id, $this->companyA->id);

    $this->assertDatabaseHas('drivers', [
        'id' => $this->driver->id,
        'company_id' => $this->companyA->id,
        'name' => 'John Updated',
        'id_number' => 'ID123456UPDATED',
        'email' => 'john.updated@example.com',
        'mobile_number' => '1111222333',
        'country_code' => '+61',
        'status' => true,
    ]);
});

test('call update method does not update driver from different company', function (): void {
    $driverData = new DriverData(
        name: 'Unauthorized Update',
        id_number: 'HACK123',
        email: 'hack@example.com',
        mobile_number: '9999999999',
        country_code: '+1',
        status: true
    );

    $driverQueries = resolve(DriverQueries::class);

    expect(fn () => $driverQueries->update($driverData, $this->driver->id, $this->companyB->id))
        ->toThrow(ModelNotFoundException::class);
});

test('call changeStatus method toggles driver status', function (): void {
    expect($this->driver->status)->toBeTrue();

    $driverQueries = resolve(DriverQueries::class);
    $driverQueries->changeStatus($this->driver->id, $this->companyA->id);

    $this->assertDatabaseHas('drivers', [
        'id' => $this->driver->id,
        'status' => false,
    ]);
});

test('call changeStatus method does not change status for driver from different company', function (): void {
    $driverQueries = resolve(DriverQueries::class);

    expect(fn () => $driverQueries->changeStatus($this->driver->id, $this->companyB->id))
        ->toThrow(ModelNotFoundException::class);
});

test('drivers are scoped by company correctly', function (): void {
    Driver::factory()->count(3)->create([
        'company_id' => $this->companyA->id,
    ]);
    Driver::factory()->count(2)->create([
        'company_id' => $this->companyB->id,
    ]);

    $filterData = [
        'search_text' => '',
        'per_page' => 20,
    ];

    $driverQueries = resolve(DriverQueries::class);
    $responseA = $driverQueries->listQuery($filterData, $this->companyA->id);
    $responseB = $driverQueries->listQuery($filterData, $this->companyB->id);

    expect($responseA->total())->toBe(4); // 3 new + 1 from beforeEach
    expect($responseB->total())->toBe(2);
});

test('id_number uniqueness is enforced per company', function (): void {
    Driver::factory()->create([
        'company_id' => $this->companyB->id,
        'id_number' => 'ID123456', // Same ID number as driver in companyA
    ]);

    // Should be able to create driver with same ID number in different company
    $this->assertDatabaseCount('drivers', 2);

    $driversWithSameId = Driver::where('id_number', 'ID123456')->get();
    expect($driversWithSameId)->toHaveCount(2);
    expect($driversWithSameId->pluck('company_id')->unique())->toHaveCount(2);
});
