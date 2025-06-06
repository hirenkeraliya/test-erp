<?php

declare(strict_types=1);

use App\Domains\SaleSeason\DataObjects\SaleSeasonData;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Models\Company;
use App\Models\SaleSeason;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->saleSeasonA = SaleSeason::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'DEF',
    ]);

    $this->saleSeasonB = SaleSeason::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABC',
    ]);

    $this->saleSeasonQueries = new SaleSeasonQueries();
});

test('SaleSeason can be searched', function (): void {
    $response = $this->saleSeasonQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->saleSeasonA->name);
});

test('new sale season can be added', function (): void {
    $this->saleSeasonQueries->addNew(new SaleSeasonData('seasonName', '2024-05-09', '2024-06-09'), $this->companyId);

    $this->assertDatabaseHas('sale_seasons', [
        'name' => 'seasonName',
        'start_date' => '2024-05-09',
        'end_date' => '2024-06-09',
        'company_id' => $this->companyId,
    ]);
});

test('SaleSeason can be fetched', function (): void {
    $response = $this->saleSeasonQueries->getById($this->saleSeasonA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->saleSeasonA->name)
        ->toHaveKey('start_date', $this->saleSeasonA->start_date)
        ->toHaveKey('end_date', $this->saleSeasonA->end_date);
});

test('A sale season can be updated', function (): void {
    $this->saleSeasonQueries->update(
        new SaleSeasonData('seasonNameUpdated', '2024-05-09', '2024-06-09'),
        $this->saleSeasonA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('sale_seasons', [
        'name' => 'seasonNameUpdated',
        'start_date' => '2024-05-09',
        'end_date' => '2024-06-09',
        'company_id' => $this->companyId,
    ]);
});

test('A sale season can be soft delete', function (): void {
    $this->saleSeasonQueries->delete($this->saleSeasonA->id, $this->companyId);

    $this->assertDatabaseHas('sale_seasons', [
        'id' => $this->saleSeasonA->id,
        'name' => $this->saleSeasonA->name,
        'deleted_at' => Carbon::now(),
    ]);
});

test('getWithBasicColumns return the collection with basic details', function (): void {
    $response = $this->saleSeasonQueries->getWithBasicColumns($this->companyId);

    expect($response)->toBeCollection();
    expect($response->first()->toArray())->toHaveKey('name', $this->saleSeasonA->name);
});
