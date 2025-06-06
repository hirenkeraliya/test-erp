<?php

declare(strict_types=1);

use App\Domains\EmployeeGroup\DataObjects\EmployeeGroupData;
use App\Domains\EmployeeGroup\DataObjects\SuperAdminEmployeeGroupData;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\EmployeeGroup;
use App\Models\SuperAdmin;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->companyB = Company::factory()->create();

    $this->employeeGroupA = EmployeeGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'ABC',
    ]);
    $this->employeeGroupB = EmployeeGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'DEF',
    ]);
    $this->employeeGroupC = EmployeeGroup::factory()->create([
        'company_id' => $this->companyB->id,
        'name' => 'PQR',
        'code' => 'PQR',
    ]);

    $this->employeeGroupQueries = new EmployeeGroupQueries();
});

test('Employee groups can be searched', function (): void {
    $response = $this->employeeGroupQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('code', $this->employeeGroupB->code)
        ->toHaveKey('name', $this->employeeGroupB->name);
});

test("Employee groups are returned as per admin's company", function (): void {
    $response = $this->employeeGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->employeeGroupB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->employeeGroupA->name);
});

test('Employee groups are returned as per page', function (): void {
    $response = $this->employeeGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->employeeGroupB->name);
});

test('Employee groups can be sorted by id', function (): void {
    $response = $this->employeeGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->employeeGroupA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->employeeGroupB->name);
});

test('A new employee group can be added', function (): void {
    $admin = Admin::factory()->create();

    $this->employeeGroupQueries->addNew(
        new EmployeeGroupData(
            'Employee Group 1',
            'Employee Group Code',
            PurchaseLimitTypes::BY_SALE->value,
            12,
            LimitResetTypes::BY_MONTH->value,
            10
        ),
        $this->companyId,
        $admin
    );

    $this->assertDatabaseHas('employee_groups', [
        'name' => 'Employee Group 1',
        'code' => 'Employee Group Code',
        'company_id' => $this->companyId,
    ]);
});

test('A employee group can be fetched', function (): void {
    $response = $this->employeeGroupQueries->getById($this->employeeGroupA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->employeeGroupA->name);
});

test('A employee group can be updated', function (): void {
    $this->employeeGroupQueries->update(
        new EmployeeGroupData(
            'Employee Group 001',
            'Employee Group Code 001',
            PurchaseLimitTypes::BY_SALE->value,
            1,
            LimitResetTypes::BY_MONTH->value,
            10
        ),
        $this->employeeGroupA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('employee_groups', [
        'name' => 'Employee Group 001',
        'code' => 'Employee Group Code 001',
        'company_id' => $this->companyId,
    ]);
});

test('getEmployeeGroupsExport method returns member groups as expected', function (): void {
    $response = $this->employeeGroupQueries->getEmployeeGroupsExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->employeeGroupA->id)
        ->toHaveKey('name', $this->employeeGroupA->name);
});

test('Employee groups can be searched for super admin', function (): void {
    $response = $this->employeeGroupQueries->listQueryForSuperAdmin([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('code', $this->employeeGroupB->code)
        ->toHaveKey('name', $this->employeeGroupB->name);
});

test('Active company employees group should list', function (): void {
    $this->companyB->delete();

    $response = $this->employeeGroupQueries->listQueryForSuperAdmin([
        'search_text' => '',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(2, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('code', $this->employeeGroupB->code)
        ->toHaveKey('name', $this->employeeGroupB->name);
});

test('getSuperAdminEmployeeGroupsExport method returns member groups as expected', function (): void {
    $response = $this->employeeGroupQueries->getSuperAdminEmployeeGroupsExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->employeeGroupA->id)
        ->toHaveKey('name', $this->employeeGroupA->name);
});

test('A employee group can be updated for super admin', function (): void {
    $this->employeeGroupQueries->updateForSuperAdmin(
        new SuperAdminEmployeeGroupData(
            $this->companyId,
            'Employee Group 002',
            'Employee Group Code 002',
            PurchaseLimitTypes::BY_SALE->value,
            11,
            LimitResetTypes::BY_MONTH->value,
            12
        ),
        $this->employeeGroupA->id,
    );

    $this->assertDatabaseHas('employee_groups', [
        'name' => 'Employee Group 002',
        'code' => 'Employee Group Code 002',
    ]);
});

test('A new employee group can be added for super admin', function (): void {
    $superAdmin = SuperAdmin::factory()->create();
    $this->employeeGroupQueries->addForSuperAdmin(
        new SuperAdminEmployeeGroupData(
            $this->companyId,
            'Employee Group 13',
            'Employee Group Code 12',
            PurchaseLimitTypes::BY_SALE->value,
            12,
            LimitResetTypes::BY_MONTH->value,
            10
        ),
        $superAdmin
    );

    $this->assertDatabaseHas('employee_groups', [
        'name' => 'Employee Group 13',
        'code' => 'Employee Group Code 12',
    ]);
});
