<?php

declare(strict_types=1);

use App\Domains\UnitOfMeasure\DataObjects\UnitOfMeasureData;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Models\Company;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);

    $this->companyB = Company::factory()->create([
        'name' => 'ABCD',
        'code' => 'EFGH',
    ]);

    $this->unitOfMeasureA = UnitOfMeasure::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'DEF',
    ]);

    $this->unitOfMeasureB = UnitOfMeasure::factory()->create([
        'company_id' => $this->companyB->id,
        'name' => 'GHI',
    ]);

    $this->unitOfMeasureQueries = new UnitOfMeasureQueries();
});

test('Unit of Measure can be searched', function (): void {
    $response = $this->unitOfMeasureQueries->listQuery([
        'search_text' => $this->unitOfMeasureA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->unitOfMeasureA->name);
});

test('New unit of measure can be added', function (): void {
    $this->unitOfMeasureQueries->addNew(new UnitOfMeasureData('ABCD', true), $this->companyA->id);

    $this->assertDatabaseHas('unit_of_measures', [
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);
});

test('A unit of measure can be fetched', function (): void {
    $response = $this->unitOfMeasureQueries->getById($this->unitOfMeasureA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->unitOfMeasureA->name);
});

test('A unit of measure can be updated', function (): void {
    $this->unitOfMeasureQueries->update(
        new UnitOfMeasureData('EFGHI', true),
        $this->unitOfMeasureA->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('unit_of_measures', [
        'company_id' => $this->companyA->id,
        'name' => 'EFGHI',
    ]);
});

test(
    'getWithBasicColumns method returns the list of unit of measures',
    function (): void {
        $response = $this->unitOfMeasureQueries->getWithBasicColumns($this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->unitOfMeasureA->name);
    }
);

test(
    'getWithBasicColumnsAndDerivatives method returns the list of unit of measures with derivatives',
    function (): void {
        $unitOfMeasureDerivativeA = UnitOfMeasureDerivative::factory()->create([
            'unit_of_measure_id' => $this->unitOfMeasureA->id,
            'name' => 'DEFGHI',
        ]);

        $response = $this->unitOfMeasureQueries->getWithBasicColumnsAndDerivatives($this->companyA->id);
        expect($response->first()->toArray())
            ->toHaveKey('name', $this->unitOfMeasureA->name)
            ->toHaveKey('derivatives.0.name', $unitOfMeasureDerivativeA->name)
            ->toHaveKey('derivatives.0.ratio', $unitOfMeasureDerivativeA->ratio);
    }
);

test('existsByName method returns result as expected', function (): void {
    $response = $this->unitOfMeasureQueries->existsByName('DEF', $this->companyA->id);
    $this->assertTrue($response);

    $response = $this->unitOfMeasureQueries->existsByName('ABCDEFGH', $this->companyA->id);
    $this->assertFalse($response);
});

test('doUnitOfMeasureExists method returns boolean as expected', function (): void {
    $response = $this->unitOfMeasureQueries->doUnitOfMeasureExists($this->unitOfMeasureA->id, $this->companyA->id);
    $this->assertTrue($response);

    $response = $this->unitOfMeasureQueries->doUnitOfMeasureExists(-10, $this->companyA->id);
    $this->assertFalse($response);
});

test('getIdByName method returns unit of measure details', function (): void {
    $response = $this->unitOfMeasureQueries->getIdByName($this->unitOfMeasureA->name, $this->companyA->id);
    $this->assertEquals($this->unitOfMeasureA->id, $response);
});

test('getUnitOfMeasuresExport method returns unit of measure as expected', function (): void {
    $response = $this->unitOfMeasureQueries->getUnitOfMeasuresExport([
        'search_text' => $this->unitOfMeasureA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->unitOfMeasureA->name);
});

test(
    'getIdByNameAndCompanyId method return category id',
    function (): void {
        $response = $this->unitOfMeasureQueries->getIdByNameAndCompanyId(
            $this->unitOfMeasureA->name,
            $this->companyA->id
        );
        $this->assertEquals($this->unitOfMeasureA->id, $response);
    }
);

test('call delete method delete the unit of measure', function (): void {
    $this->unitOfMeasureQueries->delete($this->unitOfMeasureA->id, $this->companyA->id);
    $this->assertSoftDeleted($this->unitOfMeasureA);
});
