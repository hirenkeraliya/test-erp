<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerProfileData;
use App\Models\Employee;
use App\Models\Location;
use App\Models\WarehouseManager;
use Illuminate\Validation\ValidationException;

test('warehouse manager profile with the same username cannot be added', function (): void {
    WarehouseManager::factory()->create([
        'username' => 'ABCD',
    ]);
    WarehouseManagerProfileData::validate(warehouseManagerProfileData());
})->throws(ValidationException::class);

test('warehouse manager profile with the same employee cannot be added', function (): void {
    $employee = Employee::factory()->create();
    WarehouseManager::factory()->create([
        'employee_id' => $employee->id,
    ]);
    WarehouseManagerProfileData::validate(warehouseManagerProfileData($employee->id));
})->throws(ValidationException::class);

function warehouseManagerProfileData(?int $employeeId = null): array
{
    Location::factory()->create([
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    return [
        'employee_id' => $employeeId ?? 1,
        'username' => 'ABCD',
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ];
}
