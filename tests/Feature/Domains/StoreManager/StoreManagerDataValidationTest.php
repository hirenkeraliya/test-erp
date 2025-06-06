<?php

declare(strict_types=1);

use App\Domains\StoreManager\DataObjects\StoreManagerData;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Illuminate\Validation\ValidationException;

test('store manager with the same username cannot be added', function (): void {
    StoreManager::factory()->create([
        'username' => 'ABCD',
    ]);
    StoreManagerData::validate(storeManagerData());
})->throws(ValidationException::class);

test('store manager with the same employee cannot be added', function (): void {
    $employee = Employee::factory()->create();
    StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);
    StoreManagerData::validate(storeManagerData($employee->id));
})->throws(ValidationException::class);

function storeManagerData(?int $employeeId = null): array
{
    $location = Location::factory()->create();

    return [
        'employee_id' => $employeeId ?? 1,
        'username' => 'ABCD',
        'password' => '123456',
        'passcode' => '123456',
        'location_ids' => [$location->id],
        'role_ids' => [1],
    ];
}
