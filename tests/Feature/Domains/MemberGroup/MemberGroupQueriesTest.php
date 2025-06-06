<?php

declare(strict_types=1);

use App\Domains\MemberGroup\DataObjects\MemberGroupData;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Models\Company;
use App\Models\MemberGroup;
use App\Models\Product;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->memberGroupA = MemberGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'ABC',
        'type_id' => GroupTypes::MANUAL_GROUP->value,
    ]);
    $this->memberGroupB = MemberGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'DEF',
    ]);

    $this->memberGroupQueries = new MemberGroupQueries();
});

test('Member groups can be searched', function (): void {
    $response = $this->memberGroupQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('code', $this->memberGroupB->code)
        ->toHaveKey('name', $this->memberGroupB->name);
});

test("Member groups are returned as per admin's company", function (): void {
    $response = $this->memberGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->memberGroupB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->memberGroupA->name);
});

test('Member groups are returned as per page', function (): void {
    $response = $this->memberGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->memberGroupB->name);
});

test('Member groups can be sorted by id', function (): void {
    $response = $this->memberGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->memberGroupA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->memberGroupB->name);
});

test('A new member group can be added', function (): void {
    $this->memberGroupQueries->addNew(
        new MemberGroupData(
            'Member Group 1',
            'Member Group Code',
            GroupTypes::MANUAL_GROUP->value,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            [1]),
        $this->companyId
    );

    $this->assertDatabaseHas('member_groups', [
        'name' => 'Member Group 1',
        'code' => 'Member Group Code',
        'company_id' => $this->companyId,
    ]);
});

test('A member group can be fetched', function (): void {
    $response = $this->memberGroupQueries->getById($this->memberGroupA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->memberGroupA->name);
});

test('getByCompanyId return member group list', function (): void {
    $response = $this->memberGroupQueries->getByCompanyId($this->companyId);
    expect($response->first()->toArray())
        ->toHaveKey('name', $this->memberGroupA->name);
});

test('A member group can be updated', function (): void {
    $this->memberGroupQueries->update(
        new MemberGroupData(
            'Member Group 001',
            'Member Group Code 001',
            GroupTypes::MANUAL_GROUP->value,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            [1]),
        $this->memberGroupA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('member_groups', [
        'name' => 'Member Group 001',
        'code' => 'Member Group Code 001',
        'company_id' => $this->companyId,
    ]);
});

test('getMemberGroupsExport method returns member groups as expected', function (): void {
    $response = $this->memberGroupQueries->getMemberGroupsExport([
        'search_text' => 'ABC',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->memberGroupA->id)
        ->toHaveKey('name', $this->memberGroupA->name);
});

test('addProductInPivot method add member in pivot table', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->memberGroupQueries->addProductInPivot($product->id, $this->memberGroupA);

    $this->assertDatabaseHas('member_group_product', [
        'product_id' => $product->id,
        'member_group_id' => $this->memberGroupA->id,
    ]);
});

test('removeSelectedProducts method delete member in pivot table', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);
    $this->memberGroupQueries->addProductInPivot($product->id, $this->memberGroupA);
    $this->memberGroupQueries->removeSelectedProducts($this->memberGroupA->id, $this->companyId);

    $this->assertDatabaseMissing('member_group_product', [
        'product_id' => $product->id,
        'member_group_id' => $this->memberGroupA->id,
    ]);
});
