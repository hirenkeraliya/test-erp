<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\SaleReturnReason\DataObjects\SaleReturnReasonData;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Models\Company;
use App\Models\SaleReturnReason;
use App\Models\SaleReturnReasonType;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->saleReturnReasonA = SaleReturnReason::factory()->create([
        'reason' => 'Sale return reason 1',
        'company_id' => $this->companyId,
    ]);

    $this->saleReturnReasonB = SaleReturnReason::factory()->create([
        'reason' => 'Sale return reason 2',
        'company_id' => $this->companyId,
    ]);

    $this->saleReturnReasonQueries = new SaleReturnReasonQueries();
});

test('Sale return reasons can be searched', function (): void {
    $response = $this->saleReturnReasonQueries->listQuery([
        'search_text' => 'Sale return reason 1',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->saleReturnReasonA->id)
        ->toHaveKey('reason', $this->saleReturnReasonA->reason);
});

test('Sale return reasons can be sorted by id', function (): void {
    $response = $this->saleReturnReasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->saleReturnReasonA->id)
        ->toHaveKey('reason', $this->saleReturnReasonA->reason);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('id', $this->saleReturnReasonB->id)
        ->toHaveKey('reason', $this->saleReturnReasonB->reason);
});

test('Sale return reasons are returned as per page', function (): void {
    $response = $this->saleReturnReasonQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ], $this->companyId);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->saleReturnReasonB->id)
        ->toHaveKey('reason', $this->saleReturnReasonB->reason);
});

test('New sale return reason can be added', function (): void {
    $this->saleReturnReasonQueries->addNew(
        new SaleReturnReasonData('Sale return reason 3', true, [
            SaleReturnOrVoidSaleReasonTypes::POS->value,
        ], null, null),
        $this->companyId
    );

    $this->assertDatabaseHas('sale_return_reasons', [
        'company_id' => $this->companyId,
        'reason' => 'Sale return reason 3',
        'put_back_in_inventory' => true,
    ]);
});

test('A sale return reason can be updated', function (): void {
    $this->saleReturnReasonQueries->update(
        new SaleReturnReasonData('Sale return reason 1.1', false, [SaleReturnOrVoidSaleReasonTypes::POS->value]),
        $this->saleReturnReasonA->id,
        $this->companyId
    );

    $this->assertDatabaseHas('sale_return_reasons', [
        'company_id' => $this->companyId,
        'reason' => 'Sale return reason 1.1',
        'put_back_in_inventory' => false,
    ]);
});

test(
    'getList method returns the sale return reasons list',
    function (): void {
        $saleReturnReason = SaleReturnReason::factory()->create([
            'reason' => 'Sale return reason 3',
            'company_id' => $this->companyId,
        ]);

        SaleReturnReasonType::factory()->create([
            'sale_return_reason_id' => $saleReturnReason->id,
            'type_id' => SaleReturnOrVoidSaleReasonTypes::POS->value,
        ]);

        $response = $this->saleReturnReasonQueries->getListForPOSOrOrders($this->companyId);

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response->count())->toBe(1);

        expect($response->first()->toArray())
            ->toHaveKey('id', $saleReturnReason->id)
            ->toHaveKey('reason', $saleReturnReason->reason);
    }
);

test(
    'getByIdsAndCompanyId method returns the sale return reasons list',
    function (): void {
        $response = $this->saleReturnReasonQueries->getByIdsAndCompanyId(
            [$this->saleReturnReasonA->id],
            $this->companyId
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->saleReturnReasonA->id)
            ->toHaveKey('reason', $this->saleReturnReasonA->reason);
    }
);

test('getSaleReturnReasonsExport method returns reason as expected', function (): void {
    $response = $this->saleReturnReasonQueries->getSaleReturnReasonsExport([
        'search_text' => $this->saleReturnReasonA->reason,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->saleReturnReasonA->id)
        ->toHaveKey('reason', $this->saleReturnReasonA->reason);
});
