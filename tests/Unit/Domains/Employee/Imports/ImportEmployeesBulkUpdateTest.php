<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\Employee\Imports\ImportEmployeesBulkUpdate;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\Designation;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $employeeData = getEmployeeUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock) use ($companyId, $employeeData): void {
        $mock->shouldReceive('mobileNumberExist')
            ->once()
            ->with($employeeData['mobile_number'], $companyId)
            ->andReturn(true);

        $mock->shouldReceive('emailTakenByAnotherEmployee')
            ->once()
            ->with($employeeData['email'], $companyId, $employeeData['mobile_number'])
            ->andReturn(false);
    });

    $this->mock(EmployeeGroupQueries::class, function ($mock) use ($companyId, $employeeData): void {
        $mock->shouldReceive('employeeGroupExists')
            ->once()
            ->with($employeeData['group_name'], $companyId)
            ->andReturn(true);
    });

    $importEmployeesBulkUpdate = new ImportEmployeesBulkUpdate();
    $redirectResponse = $importEmployeesBulkUpdate->validate($employeeData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('the validate method returns error messages', function (): void {
    $companyId = 1;

    $employeeData = getEmployeeUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock) use ($companyId, $employeeData): void {
        $mock->shouldReceive('emailTakenByAnotherEmployee')
            ->once()
            ->with($employeeData['email'], $companyId, $employeeData['mobile_number'])
            ->andReturn(true);

        $mock->shouldReceive('mobileNumberExist')
            ->once()
            ->with($employeeData['mobile_number'], $companyId)
            ->andReturn(false);
    });

    $this->mock(EmployeeGroupQueries::class, function ($mock) use ($companyId, $employeeData): void {
        $mock->shouldReceive('employeeGroupExists')
            ->once()
            ->with($employeeData['group_name'], $companyId)
            ->andReturn(false);
    });

    $importEmployeesBulkUpdate = new ImportEmployeesBulkUpdate();
    $redirectResponse = $importEmployeesBulkUpdate->validate($employeeData, $importRecord);
    $this->assertEquals(3, is_countable($redirectResponse) ? count($redirectResponse) : 0);
});

test('save method works for the employee details update', function (): void {
    $companyId = 1;
    $groupId = 1;
    $designationId = 1;

    $employeeData = getEmployeeUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::EMPLOYEES_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(DesignationQueries::class, function ($mock) use ($employeeData, $companyId, $designationId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($employeeData['designation_name'], $companyId)
            ->andReturn($designationId);
    });

    $this->mock(EmployeeGroupQueries::class, function ($mock) use ($employeeData, $companyId, $groupId): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($employeeData['group_name'], $companyId)
            ->andReturn($groupId);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByMobileNumber')
            ->times(1);
    });

    $importEmployeesBulkUpdate = new ImportEmployeesBulkUpdate();
    $importEmployeesBulkUpdate->save($employeeData, $importRecord);
    $this->assertTrue(true);
});

function getEmployeeUpdateData(): array
{
    $designation = Designation::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'abcd',
    ]);

    return [
        'company_id' => 1,
        'designation_name' => $designation->name,
        'first_name' => 'first_name',
        'last_name' => '',
        'email' => 'abcdef@ddd.com',
        'group_name' => '',
        'mobile_number' => '1234567890',
        'home_contact' => '',
        'address_line_1' => 'sasas',
        'address_line_2' => '',
        'city' => '',
        'area_code' => '',
        'date_of_joining' => '',
        'primary_contact_name' => '',
        'primary_contact_phone' => '',
        'staff_id' => 1,
        'ic_number' => '',
        'job_type' => JobTypes::FULL_TIME->name,
        'photo' => null,
    ];
}
