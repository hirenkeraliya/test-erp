<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerData;
use App\Models\Employee;
use App\Models\Location;
use App\Models\WarehouseManager;
use Illuminate\Validation\ValidationException;

test('warehouse manager with the same username cannot be added', function (): void {
    WarehouseManager::factory()->create([
        'username' => 'ABCD',
    ]);
    WarehouseManagerData::validate(warehouseManagerData());
})->throws(ValidationException::class);

test('warehouse manager with the same employee cannot be added', function (): void {
    $employee = Employee::factory()->create();
    WarehouseManager::factory()->create([
        'employee_id' => $employee->id,
    ]);
    WarehouseManagerData::validate(warehouseManagerData($employee->id));
})->throws(ValidationException::class);

function warehouseManagerData(?int $employeeId = null): array
{
    $location = Location::factory()->create([
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    return [
        'employee_id' => $employeeId ?? 1,
        'username' => 'ABCD',
        'password' => '123456',
        'location_ids' => [$location->id],
    ];
}
