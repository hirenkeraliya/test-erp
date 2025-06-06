<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\Imports\ImportPromoter;
use App\Domains\Promoter\PromoterQueries;
use App\Models\Admin;
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
            'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
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
            'password' => '',
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

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->with($company->id)
                ->once()
                ->andReturn($company);
        });

        $this->mock(PromoterQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsByEmployeeId')
                ->once();
        });

        $importPromoter = new ImportPromoter();
        $redirectResponse = $importPromoter->validate($promoterData, $importRecord);
        $this->assertEquals(8, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls addNew method to store promoter details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::PROMOTERS->value,
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

    $promoterRecord = [
        'first_name' => $employee->first_name,
        'mobile_number' => $employee->mobile_number,
        'code' => 'A123',
        'username' => 'ABCDE',
        'password' => '123456789',
        'monthly_sales_target' => 11,
        'default_commission_amount_percentage' => 12,
        'monthly_target_commission_percentage' => 12,
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

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $importPromoter = new ImportPromoter();
    $importPromoter->save($promoterRecord, $importRecord);
    $this->assertTrue(true);
});
