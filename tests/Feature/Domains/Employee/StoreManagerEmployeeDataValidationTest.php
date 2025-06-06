<?php

declare(strict_types=1);

use App\Domains\Employee\DataObjects\StoreManagerEmployeeData;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('store manager employee data validation succeeds with valid data', function (): void {
    $companyId = Company::factory()->create()->id;
    $designationId = Designation::factory()->create([
        'company_id' => $companyId,
    ])->id;
    $groupId = EmployeeGroup::factory()->create([
        'company_id' => $companyId,
    ])->id;
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $records = createStoreManagerEmployeeRecord(
        'validemail@example.com',
        '601111111111',
        $companyId,
        $designationId,
        $groupId
    );

    $employee = Employee::factory()->create([
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->create([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $records['photo'] = $uploadedFile;
    $request = new Request($records);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(StoreManagerEmployeeData::rules($request));

    $this->assertTrue(true);
});

test('store manager employee data validation fails with invalid email', function (): void {
    $companyId = Company::factory()->create()->id;
    $designationId = Designation::factory()->create([
        'company_id' => $companyId,
    ])->id;
    $groupId = EmployeeGroup::factory()->create([
        'company_id' => $companyId,
    ])->id;
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $records = createStoreManagerEmployeeRecord('invalid-email', '601111111111', $companyId, $designationId, $groupId);
    $records['photo'] = $uploadedFile;

    $employee = Employee::factory()->create([
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->create([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $request = new Request($records);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $request->validate(StoreManagerEmployeeData::rules($request));
})->throws(ValidationException::class);

function createStoreManagerEmployeeRecord(
    string $email,
    string $mobileNumber,
    int $companyId,
    int $designationId,
    int $groupId
): array {
    return Employee::factory()->make([
        'company_id' => $companyId,
        'designation_id' => $designationId,
        'email' => $email,
        'mobile_number' => $mobileNumber,
        'group_id' => $groupId,
    ])->toArray();
}
