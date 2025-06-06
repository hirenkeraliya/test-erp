<?php

declare(strict_types=1);

use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\CashMovementReason\DataObjects\CashMovementReasonData;
use App\Models\CashMovementReason;
use App\Models\Company;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->cashMovementReasonA = CashMovementReason::factory()->create([
        'reason' => 'Cash movement reason 1',
        'company_id' => $this->companyId,
    ]);
    $this->cashMovementReasonB = CashMovementReason::factory()->create([
        'reason' => 'Cash movement reason 2',
        'company_id' => $this->companyId,
    ]);

    $this->cashMovementReasonQueries = new CashMovementReasonQueries();
});

test('Cash Flow Codes can be searched', function (): void {
    $response = $this->cashMovementReasonQueries->listQuery([
        'search_text' => 'Cash movement reason 1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->cashMovementReasonA->id)
        ->toHaveKey('reason', $this->cashMovementReasonA->reason);
});

test('New cash movement reason can be added', function (): void {
    $this->cashMovementReasonQueries->addNew(new CashMovementReasonData('Cash movement reason 3', 1), $this->companyId);

    $this->assertDatabaseHas('cash_movement_reasons', [
        'company_id' => $this->companyId,
        'reason' => 'Cash movement reason 3',
        'type_id' => 1,
    ]);
});

test('A cash movement reason can be updated', function (): void {
    $this->cashMovementReasonQueries->update(
        new CashMovementReasonData('Cash movement reason 1.1', 2),
        $this->cashMovementReasonA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('cash_movement_reasons', [
        'company_id' => $this->companyId,
        'reason' => 'Cash movement reason 1.1',
        'type_id' => 2,
    ]);
});

test(
    'getList method returns the Cash Flow Codes list',
    function (): void {
        $response = $this->cashMovementReasonQueries->getList($this->companyId);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->cashMovementReasonA->id)
            ->toHaveKey('reason', $this->cashMovementReasonA->reason)
            ->toHaveKey('type_id', $this->cashMovementReasonA->type_id);
    }
);

test('getCashMovementReasonsExport method returns reason as expected', function (): void {
    $response = $this->cashMovementReasonQueries->getCashMovementReasonsExport([
        'search_text' => $this->cashMovementReasonA->reason,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->cashMovementReasonA->id)
        ->toHaveKey('reason', $this->cashMovementReasonA->reason)
        ->toHaveKey('type_id', $this->cashMovementReasonA->type_id);
});
