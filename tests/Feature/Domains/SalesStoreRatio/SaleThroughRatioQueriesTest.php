<?php

declare(strict_types=1);

use App\Domains\SaleThroughRatio\DataObjects\SaleThroughRatioData;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Models\Company;
use App\Models\SaleThroughRatio;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->saleThroughRatioA = SaleThroughRatio::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABCD',
    ]);

    $this->saleThroughRatioQueries = new SaleThroughRatioQueries();
});

test('Sale Through Ratio can be searched', function (): void {
    SaleThroughRatio::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'XYZ',
    ]);

    $response = $this->saleThroughRatioQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->saleThroughRatioA->name);
});

test('New Sale Through Ratio can be added', function (): void {
    $newSaleThroughRatioRecord = [
        'name' => 'ABCDEF',
        'percentage' => 10,
        'description' => 'test',
    ];

    $this->saleThroughRatioQueries->addNew(
        new SaleThroughRatioData(...$newSaleThroughRatioRecord),
        $this->companyA->id
    );

    $this->assertDatabaseHas('sale_through_ratios', $newSaleThroughRatioRecord);
});

test('A Sale Through Ratio can be fetched', function (): void {
    $response = $this->saleThroughRatioQueries->getById($this->saleThroughRatioA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->saleThroughRatioA->name);
});

test('A Sale Through Ratio can be updated', function (): void {
    $newSaleThroughRatioRecord = [
        'name' => 'Test Name',
        'percentage' => 10,
        'description' => ' test',
    ];

    $this->saleThroughRatioQueries->update(
        new SaleThroughRatioData(...$newSaleThroughRatioRecord),
        $this->saleThroughRatioA->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('sale_through_ratios', $newSaleThroughRatioRecord);
});

test('getSaleThroughRatiosExport method returns name as expected', function (): void {
    $response = $this->saleThroughRatioQueries->getSaleThroughRatiosExport([
        'search_text' => $this->saleThroughRatioA->name,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleThroughRatioA->id)
        ->toHaveKey('name', $this->saleThroughRatioA->name);
});

test('Get Grade name for export PDF headers', function (): void {
    $response = $this->saleThroughRatioQueries->getGradeNameForFilter($this->saleThroughRatioA->id);

    $this->assertIsString($response);
});
