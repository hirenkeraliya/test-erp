<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\Enums\StoreManagerBulkUpdateImportColumns;
use App\Domains\StoreManager\Imports\ImportStoreManagerBulkUpdate;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

test('the validate method returns blank array when no error in given details', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    setCompanyIdInSession($company->id);

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $company->id,
        'created_by_id' => 1,
    ]);

    $storeManagerData = [
        'first_name' => 'test',
        'mobile_number' => 1_234_567_890,
        'locations' => 'asdf',
        'brands' => 'zxc',
        'roles' => 'qwer',
        'username' => 'zzz',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->name,
        'price_override_limit_percentage_for_item' => 10,
        'price_override_limit_percentage_for_cart' => 20,
        'can_manage_wholesale' => '1',
    ];

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('doEmployeeNameExist')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('getIdByNameAndMobileNumber')
            ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('doStoreNamesExists')
            ->once()
            ->andReturn(true);
    });
    $this->mock(RoleQueries::class, function ($mock): void {
        $mock->shouldNotReceive('doRoleNamesExists')
            ->once()
            ->andReturn(true);
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldNotReceive('doBrandNamesExists')
            ->once()
            ->andReturn(true);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->with($company->id)
            ->andReturn(new Company());
    });

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('doExistsByEmployeeId')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('usernameTakenByAnotherStoreManager')
            ->once()
            ->andReturn(false);
    });

    $importStoreManagerBulkUpdate = new ImportStoreManagerBulkUpdate();
    $redirectResponse = $importStoreManagerBulkUpdate->validate($storeManagerData, $importRecord);
    $this->assertEquals([], $redirectResponse);
});

test(
    'first_name, mobile_number, stores, username are required for import record',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'name' => 'Test Company',
            'default_country_id' => 1,
        ]);

        setCompanyIdInSession($company->id);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $company->id,
            'created_by_id' => 1,
        ]);

        $storeManagerData = [
            'first_name' => '',
            'mobile_number' => 1_234_567_890,
            'locations' => '',
            'brands' => '',
            'roles' => '',
            'username' => '',
            'password' => '',
            'passcode' => '',
            'price_override_type' => '',
            'price_override_limit_percentage_for_item' => '',
            'price_override_limit_percentage_for_cart' => '',
            'can_manage_wholesale' => '',
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
        $this->mock(RoleQueries::class, function ($mock): void {
            $mock->shouldNotReceive('doRoleNamesExists');
        });

        $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldNotReceive('doBrandNamesExists')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getConfigurationColumnsById')
                ->once()
                ->with($company->id)
                ->andReturn(new Company());
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsByEmployeeId')
                ->once();
            $mock->shouldReceive('usernameTakenByAnotherStoreManager')
              ->once();
        });

        $importStoreManagerBulkUpdate = new ImportStoreManagerBulkUpdate();
        $redirectResponse = $importStoreManagerBulkUpdate->validate($storeManagerData, $importRecord);
        $this->assertEquals(8, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls updateByMobileNumber method to store store manager details', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'allow_price_override_cart_level' => true,
        'default_country_id' => 1,
    ]);

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $company->id,
        'type_id' => ImportTypes::STORE_MANAGERS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'designation_id' => 1,
        'mobile_number' => 1_234_567_890,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
    ]);

    $brand->company = $company;

    $role = Role::factory()->make([
        'name' => 'first_role',
        'guard_name' => 'store_manager',
    ]);

    $storeManagerRecord = [
        'first_name' => $employee->first_name,
        'mobile_number' => $employee->mobile_number,
        'username' => 'abcde',
        'locations' => $location->name,
        'brands' => $brand->name,
        'roles' => $role->name,
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->name,
        'price_override_limit_percentage_for_item' => 10,
        'price_override_limit_percentage_for_cart' => 12,
        'can_manage_wholesale' => 'Yes',
    ];

    $this->mock(EmployeeQueries::class, function ($mock) use ($employee): void {
        $mock->shouldReceive('getIdByNameAndMobileNumber')
            ->once()
            ->with($employee->first_name, $employee->mobile_number, 1)
            ->andReturn($employee->id);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->with($company->id)
            ->andReturn(new Company());
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getIdAndNameByNames')
            ->once()
            ->andReturn(new Collection([$location]));
    });

    $this->mock(RoleQueries::class, function ($mock) use ($role): void {
        $mock->shouldReceive('getIdAndNameByNames')
            ->once()
            ->andReturn(new Collection([$role]));
    });

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('existsByNames')
            ->once()
            ->andReturn(new Collection([$brand]));
    });

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByMobileNumber')
            ->once();
    });

    $importStoreManagerBulkUpdate = new ImportStoreManagerBulkUpdate();
    $importStoreManagerBulkUpdate->save($storeManagerRecord, $importRecord);
});

test('validate import Payment Type Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = StoreManagerBulkUpdateImportColumns::getArrayValues();

    $importStoreManagerBulkUpdate = new ImportStoreManagerBulkUpdate();
    $response = $importStoreManagerBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});
