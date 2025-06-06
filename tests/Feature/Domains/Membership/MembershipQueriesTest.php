<?php

declare(strict_types=1);

use App\Domains\Membership\DataObjects\MembershipData;
use App\Domains\Membership\MembershipQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Membership;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->membershipA = Membership::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABCD',
        'lifetime_value' => 5.5,
    ]);
    $this->membershipB = Membership::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'XYZW',
        'lifetime_value' => 7.5,
    ]);

    $this->membershipQueries = new MembershipQueries();
});

test('Memberships can be searched', function (): void {
    $response = $this->membershipQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->membershipA->name);
});

test('A membership can be fetched', function (): void {
    $response = $this->membershipQueries->getById($this->membershipA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->membershipA->name)
        ->toHaveKey('lifetime_value', $this->membershipA->lifetime_value);
});

test('New membership can be added', function (): void {
    $admin = Admin::factory()->create();

    $this->membershipQueries->addNew(new MembershipData('EFGH', 10.10, 10, 200, 300), $this->companyId, $admin);

    $this->assertDatabaseHas('memberships', [
        'company_id' => $this->companyId,
        'name' => 'EFGH',
        'lifetime_value' => 10.10,
        'loyalty_points_per_currency_unit' => 10,
    ]);
});

test('A membership can be updated', function (): void {
    $this->membershipQueries->update(
        new MembershipData('New Membership', 10.10, 10, 200, 300),
        $this->membershipA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('memberships', [
        'company_id' => $this->companyId,
        'name' => 'New Membership',
        'lifetime_value' => 10.10,
    ]);
});

test('getMembershipWhereLifetimeValueIsZero method works as expected', function (): void {
    $membership = Membership::factory()->create([
        'company_id' => $this->companyId,
        'lifetime_value' => 0.00,
    ]);

    $response = $this->membershipQueries->getMembershipWhereLifetimeValueIsZero($this->companyId);

    expect($response->toArray())
        ->toHaveKey('id', $membership->getKey());
});

test('getMembershipsExport method returns membership as expected', function (): void {
    $response = $this->membershipQueries->getMembershipsExport([
        'search_text' => $this->membershipA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
       ->toHaveKey('name', $this->membershipA->name)
       ->toHaveKey('lifetime_value', $this->membershipA->lifetime_value);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->membershipQueries->existsByName($this->membershipA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->membershipQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns the membership details', function (): void {
    $response = $this->membershipQueries->getIdByName($this->membershipA->name, $this->companyId);
    $this->assertEquals($this->membershipA->id, $response);
});
