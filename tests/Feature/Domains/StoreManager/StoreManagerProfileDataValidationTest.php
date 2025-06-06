<?php

declare(strict_types=1);

use App\Domains\StoreManager\DataObjects\StoreManagerProfileData;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Validation\ValidationException;

test('store manager profile with the same username cannot be added', function (): void {
    StoreManager::factory()->create([
        'username' => 'ABCD',
    ]);
    StoreManagerProfileData::validate(storeManagerProfileData());
})->throws(ValidationException::class);

test('store manager profile with the same employee cannot be added', function (): void {
    $employee = Employee::factory()->create();
    StoreManager::factory()->create([
        'employee_id' => $employee->id,
    ]);
    StoreManagerProfileData::validate(storeManagerProfileData($employee->id));
})->throws(ValidationException::class);

function storeManagerProfileData(?int $employeeId = null): array
{
    return [
        'employee_id' => $employeeId ?? 1,
        'username' => 'ABCD',
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ];
}
