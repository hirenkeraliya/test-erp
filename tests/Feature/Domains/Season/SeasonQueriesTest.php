<?php

declare(strict_types=1);

use App\Domains\Season\DataObjects\SeasonData;
use App\Domains\Season\SeasonQueries;
use App\Models\Company;
use App\Models\Season;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->seasonA = Season::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
        'code' => 'JKL',
    ]);
    $this->seasonB = Season::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'code' => 'XYZ',
    ]);

    $this->seasonQueries = new SeasonQueries();
});

test('Seasons can be searched', function (): void {
    $response = $this->seasonQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('company_id', $this->seasonA->company_id)
        ->toHaveKey('name', $this->seasonA->name);
});

test("Seasons are returned as per admin's company", function (): void {
    Season::factory()->create();

    $response = $this->seasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->seasonB->name);
    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->seasonA->name);
});

test('Seasons are returned as per page', function (): void {
    $response = $this->seasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->seasonB->name);
});

test('Seasons can be sorted by id', function (): void {
    $response = $this->seasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->seasonA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->seasonB->name);
});

test('new seasons can be added', function (): void {
    $this->seasonQueries->addNew(new SeasonData('seasonName', 'seasonCode'), $this->companyId);

    $this->assertDatabaseHas('seasons', [
        'name' => 'seasonName',
        'code' => 'seasonCode',
        'company_id' => $this->companyId,
    ]);
});

test('A season can be fetched', function (): void {
    $response = $this->seasonQueries->getById($this->seasonA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->seasonA->name)
        ->toHaveKey('code', $this->seasonA->code);
});

test('A season can be updated', function (): void {
    $this->seasonQueries->update(
        new SeasonData('seasonNameUpdate', 'seasonCodeUpdate'),
        $this->seasonA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('seasons', [
        'name' => 'seasonNameUpdate',
        'code' => 'seasonCodeUpdate',
        'company_id' => $this->companyId,
    ]);
});

test('seasons can be fetched', function (): void {
    $response = $this->seasonQueries->getWithBasicColumns($this->companyId);

    expect($response[0])
        ->toHaveKey('id', $this->seasonA->id)
        ->toHaveKey('name', $this->seasonA->name);
});

test('existsByName method returns result as expected', function (): void {
    $response = $this->seasonQueries->existsByName($this->seasonA->name, $this->companyId);
    $this->assertTrue($response);

    $response = $this->seasonQueries->existsByName('ABCDEFGH', $this->companyId);
    $this->assertFalse($response);
});

test('getIdByName method returns season details', function (): void {
    $response = $this->seasonQueries->getIdByName($this->seasonA->name, $this->companyId);
    $this->assertEquals($this->seasonA->id, $response);
});

test('getSeasonsExport method returns seasons as expected', function (): void {
    $response = $this->seasonQueries->getSeasonsExport([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->seasonA->id)
        ->toHaveKey('name', $this->seasonA->name);
});
