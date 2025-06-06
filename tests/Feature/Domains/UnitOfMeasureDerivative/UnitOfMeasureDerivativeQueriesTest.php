<?php

declare(strict_types=1);

use App\Domains\UnitOfMeasureDerivative\DataObjects\UnitOfMeasureDerivativeData;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Company;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'WXYZ',
        'code' => 'ZYXW',
    ]);

    $this->unitOfMeasure = UnitOfMeasure::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'DEF',
    ]);

    $this->unitOfMeasureDerivateA = UnitOfMeasureDerivative::factory()->create([
        'unit_of_measure_id' => $this->unitOfMeasure->id,
        'name' => 'GHI',
    ]);

    $this->unitOfMeasureDerivateB = UnitOfMeasureDerivative::factory()->create([
        'unit_of_measure_id' => $this->unitOfMeasure->id,
        'name' => 'abcd',
    ]);

    $this->unitOfMeasureDerivativeQueries = new UnitOfMeasureDerivativeQueries();
});

test('Unit of Measure derivatives can be searched', function (): void {
    $response = $this->unitOfMeasureDerivativeQueries->listQuery([
        'search_text' => $this->unitOfMeasureDerivateA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->unitOfMeasure->id, $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->unitOfMeasureDerivateA->name);
});

test('New unit of measure can be added', function (): void {
    $this->unitOfMeasureDerivativeQueries->addNew(
        new UnitOfMeasureDerivativeData('derivate', 10.50),
        $this->unitOfMeasure->id
    );

    $this->assertDatabaseHas('unit_of_measure_derivatives', [
        'unit_of_measure_id' => $this->unitOfMeasure->id,
        'name' => 'derivate',
        'ratio' => 10.50,
    ]);
});

test('A unit of measure can be fetched', function (): void {
    $response = $this->unitOfMeasureDerivativeQueries->getById(
        $this->unitOfMeasure->id,
        $this->unitOfMeasureDerivateA->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $this->unitOfMeasureDerivateA->id)
        ->toHaveKey('name', $this->unitOfMeasureDerivateA->name)
        ->toHaveKey('ratio', $this->unitOfMeasureDerivateA->ratio);
});

test('A unit of measure can be updated', function (): void {
    $this->unitOfMeasureDerivativeQueries->update(
        new UnitOfMeasureDerivativeData('EFGHI', 20.50),
        $this->unitOfMeasure->id,
        $this->unitOfMeasureDerivateA->id
    );

    $this->assertDatabaseHas('unit_of_measure_derivatives', [
        'unit_of_measure_id' => $this->unitOfMeasure->id,
        'name' => 'EFGHI',
        'ratio' => 20.50,
    ]);
});

test(
    'getList method returns the list of unit of measure derivatives',
    function (): void {
        $response = $this->unitOfMeasureDerivativeQueries->getList($this->unitOfMeasure->id);

        expect($response->first()->toArray())
            ->toHaveKey('name', $this->unitOfMeasureDerivateA->name)
            ->toHaveKey('ratio', $this->unitOfMeasureDerivateA->ratio);
    }
);

test('getDerivativesExport method returns derivatives as expected', function (): void {
    $response = $this->unitOfMeasureDerivativeQueries->getDerivativesExport([
        'search_text' => $this->unitOfMeasureDerivateA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->unitOfMeasure->id, $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->unitOfMeasureDerivateA->id)
        ->toHaveKey('name', $this->unitOfMeasureDerivateA->name);
});

test('getDerivativesWithUnitsByName method returns derivative as expected', function (): void {
    $response = $this->unitOfMeasureDerivativeQueries->getDerivativesWithUnitsByName(
        $this->unitOfMeasureDerivateA->name,
        $this->company->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $this->unitOfMeasureDerivateA->id)
        ->toHaveKey('name', $this->unitOfMeasureDerivateA->name)
        ->toHaveKey('ratio', $this->unitOfMeasureDerivateA->ratio);
});

test('getByUnitOfMeasureIds method returns derivative as expected', function (): void {
    $response = $this->unitOfMeasureDerivativeQueries->getByUnitOfMeasureIds(
        [$this->unitOfMeasureDerivateA->unit_of_measure_id],
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->unitOfMeasureDerivateA->id)
        ->toHaveKey('unit_of_measure_id', $this->unitOfMeasureDerivateA->unit_of_measure_id)
        ->toHaveKey('name', $this->unitOfMeasureDerivateA->name)
        ->toHaveKey('ratio', $this->unitOfMeasureDerivateA->ratio);
});
