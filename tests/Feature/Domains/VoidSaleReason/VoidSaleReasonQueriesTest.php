<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\VoidSaleReason\DataObjects\VoidSaleReasonData;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Models\Company;
use App\Models\VoidSaleReason;
use App\Models\VoidSaleReasonType;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create();

    $this->voidSaleReasonA = VoidSaleReason::factory()->create([
        'company_id' => $this->companyA->id,
        'reason' => 'ABCD',
    ]);

    $this->voidSaleReasonQueries = new VoidSaleReasonQueries();
});

test('Void Codes can be searched', function (): void {
    VoidSaleReason::factory()->create([
        'company_id' => $this->companyA->id,
        'reason' => 'XYZ',
    ]);

    $response = $this->voidSaleReasonQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('reason', $this->voidSaleReasonA->reason);
});

test('New Void Sale Reason can be added', function (): void {
    $this->voidSaleReasonQueries->addNew(
        new VoidSaleReasonData('Void Codes', [SaleReturnOrVoidSaleReasonTypes::POS->value]),
        $this->companyA->id
    );

    $this->assertDatabaseHas('void_sale_reasons', [
        'company_id' => $this->companyA->id,
        'reason' => 'Void Codes',
    ]);
});

test('A Void Sale Reason can be fetched', function (): void {
    $response = $this->voidSaleReasonQueries->getById($this->voidSaleReasonA->id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('reason', $this->voidSaleReasonA->reason);
});

test('A Void Sale Reason can be updated', function (): void {
    $this->voidSaleReasonQueries->update(
        new VoidSaleReasonData('Sale return reason 2', [SaleReturnOrVoidSaleReasonTypes::POS->value]),
        $this->voidSaleReasonA->id,
        $this->companyA->id
    );

    $this->assertDatabaseHas('void_sale_reasons', [
        'company_id' => $this->companyA->id,
        'reason' => 'Sale return reason 2',
    ]);
});

test(
    'getListForPOS method returns the Void Codes list',
    function (): void {
        $voidSaleReason = VoidSaleReason::factory()->create([
            'company_id' => $this->companyA->id,
            'reason' => 'ABCD1',
        ]);

        VoidSaleReasonType::factory()->create([
            'void_sale_reason_id' => $voidSaleReason->id,
            'type_id' => SaleReturnOrVoidSaleReasonTypes::POS->value,
        ]);

        $response = $this->voidSaleReasonQueries->getListForPOSOrOrders($this->companyA->id);

        expect($response)->toBeInstanceOf(Collection::class);

        expect($response->count())->toBe(1);

        expect($response->first()->toArray())
            ->toHaveKey('id', $voidSaleReason->id)
            ->toHaveKey('reason', $voidSaleReason->reason);
    }
);

test('getVoidSaleReasonsExport method returns counter as expected', function (): void {
    $response = $this->voidSaleReasonQueries->getVoidSaleReasonsExport([
        'search_text' => $this->voidSaleReasonA->reason,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->voidSaleReasonA->id)
        ->toHaveKey('reason', $this->voidSaleReasonA->reason);
});
