<?php

declare(strict_types=1);

use App\Domains\Denomination\DataObjects\DenominationData;
use App\Domains\Denomination\DenominationQueries;
use App\Models\Company;
use App\Models\Denomination;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->denominationA = Denomination::factory()->create([
        'company_id' => $this->companyId,
        'denomination' => 100,
    ]);
    $this->denominationB = Denomination::factory()->create([
        'company_id' => $this->companyId,
        'denomination' => 200,
    ]);

    $this->denominationQueries = new DenominationQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('Denominations can be searched', function (): void {
    $response = $this->denominationQueries->listQuery([
        'search_text' => 100,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('denomination', $this->denominationA->denomination);
});

test('A denomination can be fetched', function (): void {
    $response = $this->denominationQueries->getById($this->denominationA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('denomination', $this->denominationA->denomination);
});

test('New denomination can be added', function (): void {
    $this->denominationQueries->addNew(new DenominationData(150), $this->companyId);

    $this->assertDatabaseHas('denominations', [
        'company_id' => $this->companyId,
        'denomination' => 150,
    ]);
});

test('A denomination can be updated', function (): void {
    $this->denominationQueries->update(new DenominationData(111), $this->denominationA->id, $this->companyId);

    $this->assertDatabaseHas('denominations', [
        'company_id' => $this->companyId,
        'denomination' => 111,
    ]);
});

test('getList method returns the denomination list', function (): void {
    $response = $this->denominationQueries->getList($this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->denominationA->id)
        ->toHaveKey('denomination', $this->denominationA->denomination);
});

test('get all denomination list by company id', function (): void {
    $response = $this->denominationQueries->getByCompanyId($this->companyId);

    expect($response[0])
        ->toHaveKey('denomination', $this->denominationA->denomination);
});

test('A denomination can be deleted', function (): void {
    $this->denominationQueries->delete($this->denominationA->id, $this->companyId);

    $this->assertDatabaseMissing('denominations', [
        'id' => $this->denominationA->id,
        'company_id' => $this->companyId,
        'denomination' => 111,
    ]);
});

test('getDenominationsExport method returns denomination as expected', function (): void {
    $response = $this->denominationQueries->getDenominationsExport([
        'search_text' => '100',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->denominationA->id)
        ->toHaveKey('denomination', $this->denominationA->denomination);
});
