<?php

declare(strict_types=1);

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\CashierGroup\DataObjects\CashierGroupData;
use App\Models\Admin;
use App\Models\CashierGroup;
use App\Models\Company;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->cashierGroupA = CashierGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
    ]);
    $this->cashierGroupB = CashierGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
    ]);

    $this->cashierGroupQueries = new CashierGroupQueries();
});

test('Cashier groups can be searched', function (): void {
    $response = $this->cashierGroupQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('company_id', $this->cashierGroupB->company_id)
        ->toHaveKey('name', $this->cashierGroupB->name);
});

test("Cashier groups are returned as per admin's company", function (): void {
    $response = $this->cashierGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->cashierGroupB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->cashierGroupA->name);
});

test('Cashier groups are returned as per page', function (): void {
    $response = $this->cashierGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->cashierGroupB->name);
});

test('Cashier groups can be sorted by id', function (): void {
    $response = $this->cashierGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->cashierGroupA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->cashierGroupB->name);
});

test('A new cashier group can be added', function (): void {
    $admin = Admin::factory()->create();
    $this->cashierGroupQueries->addNew(
        new CashierGroupData('Cashier Group 1', 1, 7.2, [1, 2], 10),
        $this->companyId,
        $admin
    );

    $this->assertDatabaseHas('cashier_groups', [
        'name' => 'Cashier Group 1',
        'company_id' => $this->companyId,
        'price_override_limit_percentage_for_item' => 7.2,
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    $this->assertDatabaseHas('cashier_group_permissions', [
        'permission_id' => 1,
    ]);

    $this->assertDatabaseHas('cashier_group_permissions', [
        'permission_id' => 2,
    ]);
});

test('A cashier group can be fetched', function (): void {
    $response = $this->cashierGroupQueries->getByIdWithPermissions($this->cashierGroupA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->cashierGroupA->name);
});

test('A cashier group can be updated', function (): void {
    $this->cashierGroupQueries->update(
        new CashierGroupData('cashier Group 001', 1, 7.2, [1, 2], 10),
        $this->cashierGroupA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('cashier_groups', [
        'name' => 'cashier Group 001',
        'company_id' => $this->companyId,
        'price_override_limit_percentage_for_item' => 7.2,
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    $this->assertDatabaseHas('cashier_group_permissions', [
        'permission_id' => 1,
    ]);

    $this->assertDatabaseHas('cashier_group_permissions', [
        'permission_id' => 2,
    ]);
});

test('getCashierGroupsExport method returns cashier groups as expected', function (): void {
    $response = $this->cashierGroupQueries->getCashierGroupsExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->cashierGroupA->id)
        ->toHaveKey('name', $this->cashierGroupA->name);
});

test('A cashier group can be updated by name', function (): void {
    $this->cashierGroupQueries->updateByName(
        [
            'price_override_limit_percentage_for_item' => 10,
            'price_override_limit_percentage_for_cart' => 20,
            'permission_ids' => [1],
        ],
        $this->cashierGroupA->name,
        $this->companyId
    );

    $this->assertDatabaseHas('cashier_groups', [
        'price_override_limit_percentage_for_item' => 10,
        'price_override_limit_percentage_for_cart' => 20,
    ]);
});
