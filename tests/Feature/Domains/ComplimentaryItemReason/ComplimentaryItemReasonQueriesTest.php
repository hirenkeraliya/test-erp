<?php

declare(strict_types=1);

use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\ComplimentaryItemReason\DataObjects\ComplimentaryItemReasonData;
use App\Models\Company;
use App\Models\ComplimentaryItemReason;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->complimentaryItemReasonA = ComplimentaryItemReason::factory()->create([
        'company_id' => $this->companyA->id,
        'reason' => 'ABCD',
    ]);

    $this->complimentaryItemReasonQueries = new ComplimentaryItemReasonQueries();
});

test('Complimentary Item Reason can be searched', function (): void {
    ComplimentaryItemReason::factory()->create([
        'company_id' => $this->companyA->id,
        'reason' => 'XYZ',
    ]);

    $response = $this->complimentaryItemReasonQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('reason', $this->complimentaryItemReasonA->reason);
});

test('New Complimentary Item Reason can be added', function (): void {
    $newComplimentaryItemReasonRecord = [
        'reason' => 'ABCDEF',
    ];

    $this->complimentaryItemReasonQueries->addNew(
        new ComplimentaryItemReasonData(...$newComplimentaryItemReasonRecord),
        $this->companyA->id
    );

    $this->assertDatabaseHas('complimentary_item_reasons', $newComplimentaryItemReasonRecord);
});

test('A Complimentary Item Reason can be fetched', function (): void {
    $response = $this->complimentaryItemReasonQueries->getById(
        $this->complimentaryItemReasonA->id,
        $this->companyA->id
    );

    expect($response->toArray())
        ->toHaveKey('reason', $this->complimentaryItemReasonA->reason);
});

test('A Complimentary Item Reason can be updated', function (): void {
    $newComplimentaryItemReasonRecord = [
        'reason' => 'Test Reason',
    ];

    $this->complimentaryItemReasonQueries->update(
        new ComplimentaryItemReasonData(...$newComplimentaryItemReasonRecord),
        $this->complimentaryItemReasonA->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('complimentary_item_reasons', $newComplimentaryItemReasonRecord);
});

test(
    'getList method returns the complimentary item reasons list',
    function (): void {
        $response = $this->complimentaryItemReasonQueries->getList($this->companyA->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->complimentaryItemReasonA->id)
            ->toHaveKey('reason', $this->complimentaryItemReasonA->reason);
    }
);

test(
    'getByIdsAndCompanyId method returns the complimentary item reasons list',
    function (): void {
        $response = $this->complimentaryItemReasonQueries->getByIdsAndCompanyId(
            [$this->complimentaryItemReasonA->id],
            $this->companyA->id
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->complimentaryItemReasonA->id)
            ->toHaveKey('reason', $this->complimentaryItemReasonA->reason);
    }
);

test('getComplimentaryItemReasonsExport method returns reason as expected', function (): void {
    $response = $this->complimentaryItemReasonQueries->getComplimentaryItemReasonsExport([
        'search_text' => $this->complimentaryItemReasonA->reason,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->complimentaryItemReasonA->id)
        ->toHaveKey('reason', $this->complimentaryItemReasonA->reason);
});
