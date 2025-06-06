<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\Enums\CashierBulkUpdateImportColumns;
use App\Domains\Cashier\Imports\ImportCashierBulkUpdate;
use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Models\Admin;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;

test(
    'first_name, mobile_number, stores are required for import record',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        setCompanyIdInSession($company->id);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $company->id,
            'created_by_id' => 1,
        ]);

        $cashierData = [
            'first_name' => '',
            'mobile_number' => 1_234_567_890,
            'locations' => '',
            'username' => '',
            'cashier_group' => '',
        ];

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('doEmployeeNameExist')
                ->once();
            $mock->shouldReceive('getIdByNameAndMobileNumber')
                ->once();
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldNotReceive('doStoreNamesExists');
        });

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsByEmployeeId')
                ->once();
            $mock->shouldReceive('usernameTakenByAnotherCashier')
              ->once();
        });

        $this->mock(CashierGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByName');
        });

        $importCashier = new ImportCashierBulkUpdate();
        $redirectResponse = $importCashier->validate($cashierData, $importRecord);
        $this->assertEquals(6, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls addNew method to store cashier details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::CASHIERS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $importRecord->createdBy = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
        'mobile_number' => 1_234_567_890,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashierGroup = CashierGroup::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'name' => 'ABCD',
    ]);

    $cashierRecord = [
        'first_name' => $employee->first_name,
        'mobile_number' => $employee->mobile_number,
        'username' => 'abcde',
        'pin' => 'ABCDE',
        'cashier_group' => $cashierGroup->name,
        'locations' => $location->name,
    ];

    $this->mock(EmployeeQueries::class, function ($mock) use ($employee): void {
        $mock->shouldReceive('getIdByNameAndMobileNumber')
            ->once()
            ->with($employee->first_name, $employee->mobile_number, 1)
            ->andReturn($employee->id);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByNames')
            ->once()
            ->andReturn(new Collection([$location]));
    });

    $this->mock(CashierGroupQueries::class, function ($mock) use ($cashierGroup): void {
        $mock->shouldReceive('getIdByName')
            ->once()
            ->with($cashierGroup->name)
            ->andReturn($cashierGroup->id);
    });

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByMobileNumber')
            ->once();
    });

    $importCashierBulkUpdate = new ImportCashierBulkUpdate();
    $importCashierBulkUpdate->save($cashierRecord, $importRecord);
});

test('validate import Cashier Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = CashierBulkUpdateImportColumns::getArrayValues();

    $importCashierBulkUpdate = new ImportCashierBulkUpdate();
    $response = $importCashierBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});
