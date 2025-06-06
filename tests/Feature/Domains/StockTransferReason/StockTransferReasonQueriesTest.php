<?php

declare(strict_types=1);

use App\Domains\StockTransferReason\DataObjects\StockTransferReasonData;
use App\Domains\StockTransferReason\StockTransferReasonQueries;
use App\Models\Company;
use App\Models\StockTransferReason;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->stockTransferReasonA = StockTransferReason::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'ABCD',
        'code' => 'ABCD',
    ]);
    $this->stockTransferReasonB = StockTransferReason::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'XYZW',
        'code' => 'XYZW',
    ]);

    $this->stockTransferReasonQueries = new StockTransferReasonQueries();

    session()->put('admin_company_id', $this->companyId);
});

test('Stock Transfer Reasons can be searched', function (): void {
    $response = $this->stockTransferReasonQueries->listQuery([
        'search_text' => 'AB',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->stockTransferReasonA->name)
        ->toHaveKey('code', $this->stockTransferReasonA->code);
});

test('Stock Transfer Reason can be sorted by name', function (): void {
    $response = $this->stockTransferReasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'name',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->stockTransferReasonA->name)
        ->toHaveKey('code', $this->stockTransferReasonA->code);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->stockTransferReasonB->name)
        ->toHaveKey('code', $this->stockTransferReasonB->code);
});

test('Stock transfer Reasons are returned as per page', function (): void {
    $response = $this->stockTransferReasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->stockTransferReasonB->name)
        ->toHaveKey('code', $this->stockTransferReasonB->code);
});

test('A Stock Transfer Reason can be fetched', function (): void {
    $response = $this->stockTransferReasonQueries->getById($this->stockTransferReasonA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->stockTransferReasonA->name)
        ->toHaveKey('code', $this->stockTransferReasonA->code);
});

test('New Stock Transfer Reason can be added', function (): void {
    $this->stockTransferReasonQueries->addNew(new StockTransferReasonData('EFGH', 'EFGH'), $this->companyId);

    $this->assertDatabaseHas('stock_transfer_reasons', [
        'company_id' => $this->companyId,
        'name' => 'EFGH',
        'code' => 'EFGH',
    ]);
});

test('A Stock Transfer Reason can be updated', function (): void {
    $this->stockTransferReasonQueries->update(
        new StockTransferReasonData('IJKL', 'IJKL'),
        $this->stockTransferReasonA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('stock_transfer_reasons', [
        'company_id' => $this->companyId,
        'name' => 'IJKL',
        'code' => 'IJKL',
    ]);
});

test('getStockTransferReasonsExport method returns sizes as expected', function (): void {
    $response = $this->stockTransferReasonQueries->getStockTransferReasonsExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->stockTransferReasonA->id)
        ->toHaveKey('name', $this->stockTransferReasonA->name);
});
