<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\Enums\PromoterBulkUpdateCommissionImportColumns;
use App\Domains\Promoter\Enums\PromoterBulkUpdateImportColumns;
use App\Domains\Promoter\Imports\ImportPromoterBulkUpdate;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Models\Company;
use App\Models\ImportRecord;
use App\Models\Location;

test(
    'first_name, mobile_number, stores are required for import record and company commission type',
    function ($type): void {
        $company = Company::factory()->make([
            'id' => 1,
            'commission_type_id' => $type,
            'default_country_id' => 1,
        ]);

        setCompanyIdInSession($company->id);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $company->id,
            'created_by_id' => 1,
        ]);

        $promoterData = [
            'first_name' => '',
            'mobile_number' => 1_234_567_890,
            'locations' => '',
            'username' => '',
            'group' => '',
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

        $this->mock(PromoterQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsByEmployeeId')
                ->once();
        });

        $this->mock(PromoterGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsByName')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->once()
                ->andReturn($company);
        });

        $importPromoterBulkUpdate = new ImportPromoterBulkUpdate();
        $redirectResponse = $importPromoterBulkUpdate->validate($promoterData, $importRecord);
        if ($type === CommissionTypes::BY_DEPARTMENT->value) {
            $this->assertEquals(6, is_countable($redirectResponse) ? count($redirectResponse) : 0);
        }

        if ($type === CommissionTypes::BY_PROMOTER->value) {
            $this->assertEquals(9, is_countable($redirectResponse) ? count($redirectResponse) : 0);
        }
    }
)->with([[CommissionTypes::BY_PROMOTER->value], [CommissionTypes::BY_DEPARTMENT->value]]);

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'commission_type_id' => CommissionTypes::BY_DEPARTMENT->value,
        'default_country_id' => 1,
    ]);

    $promoterData = [
        'first_name' => 'test',
        'mobile_number' => 1_234_567_890,
        'locations' => 'qwer',
        'username' => 'asdf',
    ];

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('doEmployeeNameExist')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('getIdByNameAndMobileNumber')
            ->once()
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldNotReceive('doStoreNamesExists')
        ->once()
        ->andReturn(true);
    });

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('doExistsByEmployeeId')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('usernameTakenByAnotherPromoter')
            ->andReturn(false);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
            ->once()
            ->andReturn($company);
    });

    $importPromoterBulkUpdate = new ImportPromoterBulkUpdate();
    $redirectResponse = $importPromoterBulkUpdate->validate($promoterData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test('save method saves the data', function (): void {
    $companyId = 1;

    $promoterData = [
        'first_name' => 'test',
        'mobile_number' => 1_234_567_890,
        'locations' => 'xyz',
        'username' => 'abcd',
        'group' => 'qwert',
        'code' => '1234',
        'password' => null,
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PROMOTER_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $location = Location::factory()->make([
        'name' => 'test',
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdByNameAndMobileNumber')
            ->times(1)
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByEmployeeId')
            ->times(1);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByNames')
            ->times(1)
            ->andReturn(collect([$location]));
    });

    $this->mock(PromoterGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('getByName')
            ->times(1)
            ->andReturn(null);
    });

    $importPromoterBulkUpdate = new ImportPromoterBulkUpdate();
    $importPromoterBulkUpdate->save($promoterData, $importRecord);
});

test('validate Promoter Bulk Update Commission Import Columns by company commission type', function ($type): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
        'commission_type_id' => $type,
        'default_country_id' => 1,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
            ->once()
            ->andReturn($company);
    });

    if ($type === CommissionTypes::BY_PROMOTER->value) {
        $requiredHeaderColumns = PromoterBulkUpdateCommissionImportColumns::getArrayValues();
    }

    if ($type === CommissionTypes::BY_DEPARTMENT->value) {
        $requiredHeaderColumns = PromoterBulkUpdateImportColumns::getArrayValues();
    }

    $importPromoterBulkUpdate = new ImportPromoterBulkUpdate();
    $response = $importPromoterBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
})->with([[CommissionTypes::BY_PROMOTER->value], [CommissionTypes::BY_DEPARTMENT->value]]);
