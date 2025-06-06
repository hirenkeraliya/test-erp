<?php

declare(strict_types=1);

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\CashierGroup\Enums\CashierGroupImportColumns;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\CashierGroup\Imports\ImportCashierGroupBulkUpdate;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\Admin;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\ImportRecord;

test(
    'price_override_limit_percentage_for_cart, price_override_limit_percentage_for_item, price_override_type are required for import record',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
            'allow_price_override_cart_level' => false,
        ]);

        setCompanyIdInSession($company->id);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $company->id,
            'created_by_id' => 1,
        ]);

        $cashierGroupData = [
            'name' => 'test',
            'permissions' => [],
            'price_override_limit_percentage_for_cart' => '',
            'price_override_limit_percentage_for_item' => '',
            'price_override_type' => '',
        ];

        $this->mock(CashierGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByName')
                ->once();
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowPriceOverrideCartLevel')
                ->once()
                ->andReturn(false);
        });

        $importCashierGroupBulkUpdate = new ImportCashierGroupBulkUpdate();
        $redirectResponse = $importCashierGroupBulkUpdate->validate($cashierGroupData, $importRecord);
        $this->assertEquals(3, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test(
    'price_override_limit_percentage_for_cart is required if company level is true for import record',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
            'allow_price_override_cart_level' => true,
        ]);

        setCompanyIdInSession($company->id);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $company->id,
            'created_by_id' => 1,
        ]);

        $cashierGroupData = [
            'name' => 'test',
            'permissions' => 'sale',
            'price_override_limit_percentage_for_cart' => null,
            'price_override_limit_percentage_for_item' => null,
            'price_override_type' => 'flat',
        ];

        $this->mock(CashierGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByName')
                ->once()
                ->andReturn(true);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowPriceOverrideCartLevel')
                ->once()
                ->andReturn(true);
        });

        $importCashierGroupBulkUpdate = new ImportCashierGroupBulkUpdate();
        $redirectResponse = $importCashierGroupBulkUpdate->validate($cashierGroupData, $importRecord);
        $this->assertEquals(1, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test(
    'price_override_limit_percentage_for_item is required if price override type is percentage for import record',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
            'allow_price_override_cart_level' => true,
        ]);

        setCompanyIdInSession($company->id);

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $company->id,
            'created_by_id' => 1,
        ]);

        $cashierGroupData = [
            'name' => 'test',
            'permissions' => 'sale',
            'price_override_limit_percentage_for_cart' => '1',
            'price_override_limit_percentage_for_item' => null,
            'price_override_type' => 'percentage',
        ];

        $this->mock(CashierGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByName')
                ->once()
                ->andReturn(true);
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowPriceOverrideCartLevel')
                ->once()
                ->andReturn(true);
        });

        $importCashierGroupBulkUpdate = new ImportCashierGroupBulkUpdate();
        $redirectResponse = $importCashierGroupBulkUpdate->validate($cashierGroupData, $importRecord);
        $this->assertEquals(1, is_countable($redirectResponse) ? count($redirectResponse) : 0);
    }
);

test('It calls updateByName method to store cashier group details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::CASHIER_GROUPS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $importRecord->createdBy = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $cashierGroup = CashierGroup::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'name' => 'ABCD',
    ]);

    $cashierGroupRecord = [
        'name' => $cashierGroup->name,
        'permissions' => (string) PermissionTypes::SALE->name,
        'price_override_limit_percentage_for_cart' => 10,
        'price_override_limit_percentage_for_item' => 20,
        'price_override_type' => (string) PriceOverrideTypes::PERCENTAGE->name,
    ];

    $this->mock(CashierGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->once();
    });

    $importCashierGroupBulkUpdate = new ImportCashierGroupBulkUpdate();
    $importCashierGroupBulkUpdate->save($cashierGroupRecord, $importRecord);
});

test('validate import Cashier Group Import Columns', function (): void {
    $requiredHeaderColumns = CashierGroupImportColumns::getArrayValues();

    $importCashierGroupBulkUpdate = new ImportCashierGroupBulkUpdate();
    $response = $importCashierGroupBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});
