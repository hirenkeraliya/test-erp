<?php

declare(strict_types=1);

use App\Domains\Vehicle\DataObjects\VehicleData;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    setCompanyIdInSession($this->companyId);
});

test('vehicle validation passes when all required fields are provided', function (): void {
    $request = new Request([
        'name' => 'Delivery Truck',
        'plate_no' => 'ABC123',
        'type_of_vehicle' => 'Truck',
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
    $this->assertTrue(true);
});

test('vehicle validation passes without optional type_of_vehicle', function (): void {
    $request = new Request([
        'name' => 'Delivery Van',
        'plate_no' => 'XYZ456',
        'type_of_vehicle' => null,
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
    $this->assertTrue(true);
});

test('vehicle validation fails when required fields are missing', function (): void {
    $request = new Request([
        'name' => '',
        'plate_no' => '',
    ]);

    $request->validate(VehicleData::rules($request));
})->throws(ValidationException::class);

test('vehicle validation fails when name exceeds maximum length', function (): void {
    $request = new Request([
        'name' => str_repeat('A', 256),
        'plate_no' => 'ABC123',
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
})->throws(ValidationException::class);

test('vehicle validation fails when plate_no exceeds maximum length', function (): void {
    $request = new Request([
        'name' => 'Delivery Truck',
        'plate_no' => str_repeat('1', 51),
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
})->throws(ValidationException::class);

test('unique plate_no validation works for same company while adding', function (): void {
    Vehicle::factory()->create([
        'company_id' => $this->companyId,
        'plate_no' => 'ABC123',
    ]);

    $request = new Request([
        'name' => 'Another Vehicle',
        'plate_no' => 'ABC123',
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
})->throws(ValidationException::class);

test('unique plate_no validation allows same plate_no for different companies', function (): void {
    $otherCompanyId = Company::factory()->create()->id;

    Vehicle::factory()->create([
        'company_id' => $otherCompanyId,
        'plate_no' => 'ABC123',
    ]);

    $request = new Request([
        'name' => 'Delivery Truck',
        'plate_no' => 'ABC123',
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
    $this->assertTrue(true);
});

test('status validation accepts boolean values', function (): void {
    foreach ([true, false, 1, 0, '1', '0'] as $status) {
        $request = new Request([
            'name' => 'Delivery Truck',
            'plate_no' => 'ABC' . random_int(100, 999),
            'status' => $status,
        ]);

        $request->validate(VehicleData::rules($request));
    }

    $this->assertTrue(true);
});

test('type_of_vehicle validation accepts string values', function (): void {
    $request = new Request([
        'name' => 'Delivery Truck',
        'plate_no' => 'ABC123',
        'type_of_vehicle' => 'Heavy Duty Truck',
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
    $this->assertTrue(true);
});

test('type_of_vehicle validation fails when exceeds maximum length', function (): void {
    $request = new Request([
        'name' => 'Delivery Truck',
        'plate_no' => 'ABC123',
        'type_of_vehicle' => str_repeat('A', 256),
        'status' => true,
    ]);

    $request->validate(VehicleData::rules($request));
})->throws(ValidationException::class);
