<?php

declare(strict_types=1);

use App\Domains\Employee\DataObjects\EmployeeData;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('company wise unique email validation works while adding.', function (): void {
    $companyId = Company::factory()->create()->id;
    $designationId = Designation::factory()->create()->id;
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    Employee::factory()->create([
        'company_id' => $companyId,
        'email' => 'email@email.com',
    ]);

    $records = createEmployeeRecord('email@email.com', '601111111111', $companyId, $designationId);
    $records['photo'] = $uploadedFile;
    $request = new Request($records);

    $request->validate(EmployeeData::rules($request));
})->throws(ValidationException::class);

test('user can add different mobile_number with same company.', function (): void {
    $companyId = Company::factory()->create()->id;
    $designationId = Designation::factory()->create([
        'company_id' => $companyId,
    ])->id;
    Storage::fake('public');
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    Employee::factory()->create([
        'company_id' => $companyId,
        'mobile_number' => '601222222222',
    ]);

    $records = createEmployeeRecord('welcome@email.com', '601333333333', $companyId, $designationId);
    $records['photo'] = $uploadedFile;
    $request = new Request($records);

    $request->validate(EmployeeData::rules($request));

    $this->assertTrue(true);
});

function createEmployeeRecord(string $email, string $mobileNumber, int $companyId, int $designationId): array
{
    return Employee::factory()->make([
        'company_id' => $companyId,
        'designation_id' => $designationId,
        'email' => $email,
        'mobile_number' => $mobileNumber,
    ])->toArray();
}
