<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\Employee\Imports\ImportEmployee;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Member\Services\MemberService;
use App\Models\Admin;
use App\Models\Designation;
use App\Models\ImportRecord;

test(
    'designation_name, first_name, mobile_number,address_line_1,staff_id, and job_type are required for import record',
    function (): void {
        $companyId = 1;

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $employeeData = [
            'designation_name' => '',
            'first_name' => '',
            'mobile_number' => '',
            'email' => '',
            'address_line_1' => '',
            'staff_id' => '',
            'job_type' => '',
        ];

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('mobileNumberExist')
                ->once();

            $mock->shouldReceive('emailExist')
                ->once();
        });

        $importEmployee = new ImportEmployee();
        $redirectResponse = $importEmployee->validate($employeeData, $importRecord);
        $this->assertEquals(6, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls addNew method to  employee details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::EMPLOYEES->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $importRecord->createdBy = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $designation = Designation::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $employeeRecord = [
        'company_id' => 1,
        'designation_name' => $designation->name,
        'first_name' => 'first_name',
        'last_name' => '',
        'email' => '',
        'mobile_number' => '',
        'home_contact' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'area_code' => '',
        'date_of_joining' => '',
        'primary_contact_name' => '',
        'primary_contact_phone' => '',
        'staff_id' => 1,
        'ic_number' => '',
        'job_type' => JobTypes::FULL_TIME->name,
        'status' => true,
        'photo' => null,
    ];

    $this->mock(DesignationQueries::class, function ($mock) use ($designation): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->andReturn($designation->id);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(MemberService::class, function ($mock): void {
        $mock->shouldReceive('addNewEmployeeMember')
            ->once();
    });

    $importEmployee = new ImportEmployee();
    $importEmployee->save($employeeRecord, $importRecord);
    $this->assertTrue(true);
});
