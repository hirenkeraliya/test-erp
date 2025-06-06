<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\PromoterGroup\DataObjects\PromoterGroupData;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\PromoterGroup;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->promoterGroupA = PromoterGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
    ]);

    $this->promoterGroupB = PromoterGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
    ]);

    $this->promoterGroupQueries = new PromoterGroupQueries();
});

test('Promoter group can be searched', function (): void {
    $response = $this->promoterGroupQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->promoterGroupA->name);
});

test("Promoter groups are returned as per admin's company", function (): void {
    PromoterGroup::factory()->create();

    $response = $this->promoterGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->promoterGroupB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->promoterGroupA->name);
});

test('new promoter groups can be added', function (): void {
    $admin = Admin::factory()->create();

    $this->promoterGroupQueries->addNew(
        new PromoterGroupData('promoterName', 'promoterCode', SaleReturnOrVoidSaleReasonTypes::POS->value),
        $this->companyId,
        $admin
    );

    $this->assertDatabaseHas('promoter_groups', [
        'name' => 'promoterName',
        'code' => 'promoterCode',
        'company_id' => $this->companyId,
    ]);
});

test('A promoter groups can be fetched', function (): void {
    $response = $this->promoterGroupQueries->getById($this->promoterGroupA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->promoterGroupA->name)
        ->toHaveKey('code', $this->promoterGroupA->code);
});

test('A promoter group can be updated', function (): void {
    $this->promoterGroupQueries->update(
        new PromoterGroupData('promoterNameUpdate', 'promoterCodeUpdate', SaleReturnOrVoidSaleReasonTypes::POS->value),
        $this->promoterGroupA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('promoter_groups', [
        'name' => 'promoterNameUpdate',
        'code' => 'promoterCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('getPromoterGroupsExport method returns promoter groups as expected', function (): void {
    $response = $this->promoterGroupQueries->getPromoterGroupsExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->promoterGroupA->id)
        ->toHaveKey('name', $this->promoterGroupA->name);
});

test('it retrieves a collection of promoterGroups by their IDs', function (): void {
    $promoterGroup = PromoterGroup::factory()->create();

    $response = $this->promoterGroupQueries->getByIds([$promoterGroup->id]);
    expect($response)->toBeInstanceOf(Collection::class);
    expect(collect($response)->first()->toArray())
        ->toHaveKey('name', $promoterGroup->name);
});

test('it call getByName method and return proper response', function (): void {
    $response = $this->promoterGroupQueries->getByName($this->promoterGroupA->name, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('id', $this->promoterGroupA->id)
        ->toHaveKey('name', $this->promoterGroupA->name);
});

test('it call doExistsByName method and return proper response', function (): void {
    $response = $this->promoterGroupQueries->doExistsByName($this->promoterGroupA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->promoterGroupQueries->doExistsByName('test', $this->companyId);
    $this->assertFalse($response);
});

test('Get Promoter group name for export PDF headers', function (): void {
    $response = $this->promoterGroupQueries->getPromoterGroupNameForFilter([$this->promoterGroupA->id]);

    $this->assertIsString($response);
});
