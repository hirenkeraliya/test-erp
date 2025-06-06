<?php

declare(strict_types=1);

use App\Domains\SizeGroup\DataObjects\SizeGroupData;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\Company;
use App\Models\SizeGroup;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->sizeGroupA = SizeGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
    ]);

    $this->sizeGroupB = SizeGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
    ]);

    $this->sizeGroupQueries = new SizeGroupQueries();
});

test('Size group can be searched', function (): void {
    $response = $this->sizeGroupQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->sizeGroupA->name);
});

test("Size groups are returned as per admin's company", function (): void {
    SizeGroup::factory()->create();

    $response = $this->sizeGroupQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->sizeGroupB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->sizeGroupA->name);
});

test('new color groups can be added', function (): void {
    $this->sizeGroupQueries->addNew(new SizeGroupData('sizeName', 'sizeCode'), $this->companyId);

    $this->assertDatabaseHas('size_groups', [
        'name' => 'sizeName',
        'code' => 'sizeCode',
        'company_id' => $this->companyId,
    ]);
});

test('A size groups can be fetched', function (): void {
    $response = $this->sizeGroupQueries->getById($this->sizeGroupA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->sizeGroupA->name)
        ->toHaveKey('code', $this->sizeGroupA->code);
});

test('A size group can be updated', function (): void {
    $this->sizeGroupQueries->update(
        new SizeGroupData('sizeNameUpdate', 'sizeCodeUpdate'),
        $this->sizeGroupA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('size_groups', [
        'name' => 'sizeNameUpdate',
        'code' => 'sizeCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('getSizeGroupsExport method returns size groups as expected', function (): void {
    $response = $this->sizeGroupQueries->getSizeGroupsExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->sizeGroupA->id)
        ->toHaveKey('name', $this->sizeGroupA->name);
});

test('codeTakenByAnotherSizeGroup method returns boolean as expected', function (): void {
    $response = $this->sizeGroupQueries->codeTakenByAnotherSizeGroup(
        $this->sizeGroupA->code,
        $this->sizeGroupA->name,
        $this->companyId
    );
    $this->assertFalse($response);

    $sizeGroup = SizeGroup::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $response = $this->sizeGroupQueries->codeTakenByAnotherSizeGroup(
        $sizeGroup->code,
        $this->sizeGroupA->name,
        $this->companyId
    );
    $this->assertTrue($response);
});

test('A size group can be updated by name', function (): void {
    $this->sizeGroupQueries->updateByName(
        [
            'company_id' => $this->companyId,
            'name' => 'tests',
            'code' => '123456',
        ],
        $this->sizeGroupA->name,
        $this->companyId
    );

    $this->assertDatabaseHas('size_groups', [
        'company_id' => $this->companyId,
        'name' => 'tests',
        'code' => '123456',
    ]);
});
