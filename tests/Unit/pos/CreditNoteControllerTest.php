<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\DataObjects\PaginatedListOfActiveCreditNotesDataForPos;
use App\Domains\CreditNote\Services\CheckCreditNoteRefundRequestService;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Http\Controllers\Api\Pos\CreditNoteController;
use App\Models\Cashier;
use App\Models\CreditNote;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getPaginatedListOfActiveCreditNotes method and returns credit note list records', function (): void {
    CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $paginatedListOfActiveCreditNotesData = [
        'per_page' => 10,
        'page' => 1,
        'employee_id' => 1,
        'member_id' => 1,
        'sort_by' => 'id',
        'search_text' => '',
        'sort_direction' => 'desc',
        'after_updated_at' => null,
    ];

    $paginatedListOfActiveCreditNotesDataForPos = new PaginatedListOfActiveCreditNotesDataForPos(
        ...$paginatedListOfActiveCreditNotesData
    );

    $filterData = [
        'per_page' => $paginatedListOfActiveCreditNotesDataForPos->per_page,
        'sort_by' => $paginatedListOfActiveCreditNotesDataForPos->sort_by,
        'sort_direction' => $paginatedListOfActiveCreditNotesDataForPos->sort_direction,
        'search_text' => $paginatedListOfActiveCreditNotesDataForPos->search_text,
        'employee_id' => $paginatedListOfActiveCreditNotesDataForPos->employee_id,
        'member_id' => $paginatedListOfActiveCreditNotesDataForPos->member_id,
        'after_updated_at' => $paginatedListOfActiveCreditNotesDataForPos->after_updated_at,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($filterData): void {
        $mock->shouldReceive('getPaginatedListOfActiveCreditNotes')
            ->once()
            ->with($filterData, 1, 1)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $creditNoteController = new CreditNoteController();
    $response = $creditNoteController->getPaginatedListOfActiveCreditNotes(
        $request,
        $paginatedListOfActiveCreditNotesDataForPos
    );

    expect($response['credit_notes']->resource);
});

test(
    'it throws an exception if counter is not open But, try to get credit note list',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

        $paginatedListOfActiveCreditNotesData = [
            'per_page' => 10,
            'page' => 1,
            'employee_id' => 1,
            'member_id' => 1,
            'sort_by' => 'id',
            'search_text' => '',
            'sort_direction' => 'desc',
            'after_updated_at' => null,
        ];

        $paginatedListOfActiveCreditNotesDataForPos = new PaginatedListOfActiveCreditNotesDataForPos(
            ...$paginatedListOfActiveCreditNotesData
        );

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $creditNoteController = new CreditNoteController();
        $creditNoteController->getPaginatedListOfActiveCreditNotes(
            $request,
            $paginatedListOfActiveCreditNotesDataForPos
        );
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');

test('the refundCreditNote method refund the credit note and return it', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $creditNote->mismatches = collect([]);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $prepareArray = [
        'payment_type_id' => 1,
        'amount' => 1,
        'currency_id' => null,
        'current_currency_rate' => null,
        'currency_amount' => null,
        'store_manager_id' => 1,
        'passcode' => '123456',
    ];

    $creditNoteRefundData = new CreditNoteRefundData(...$prepareArray);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
        $mock->shouldReceive('markAsRefunded')
            ->once();
        $mock->shouldReceive('loadMismatches')
            ->once()
            ->andReturn($creditNote);
    });

    $this->mock(CheckCreditNoteRefundRequestService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->creditNoteMismatches = collect([]);
    });

    $this->mock(CreditNoteRefundQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $creditNoteController = new CreditNoteController();
    $response = $creditNoteController->refundCreditNote($request, $creditNoteRefundData, 1);

    $this->assertEquals($creditNote, $response['credit_note']->resource);
});

test('the refundCreditNote method refund the credit note and return it and set mismatches', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $creditNote->mismatches = collect([]);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $prepareArray = [
        'payment_type_id' => 1,
        'amount' => 1,
        'store_manager_id' => 1,
        'currency_id' => null,
        'current_currency_rate' => null,
        'currency_amount' => null,
        'passcode' => '123456',
    ];

    $creditNoteRefundData = new CreditNoteRefundData(...$prepareArray);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
        $mock->shouldReceive('markAsRefunded')
            ->once();
        $mock->shouldReceive('loadMismatches')
            ->once()
            ->andReturn($creditNote);
    });

    $this->mock(CheckCreditNoteRefundRequestService::class, function ($mock): void {
        $mock->shouldReceive('setDetails')
            ->once();
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->creditNoteMismatches = collect(['test']);
    });

    $this->mock(CreditNoteRefundQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $creditNoteController = new CreditNoteController();
    $response = $creditNoteController->refundCreditNote($request, $creditNoteRefundData, 1);

    $this->assertEquals($creditNote, $response['credit_note']->resource);
});

test('it calls the getCreditNoteDetails method and returns the credit note details as expected', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getCreditNoteDetails')
            ->once()
            ->with(1, 1, $creditNote->id)
            ->andReturn(new CreditNote([]));
    });

    $creditNoteController = new CreditNoteController();
    $response = $creditNoteController->getCreditNoteDetails($request, $creditNote->id);

    expect($response['credit_note']->resource);
});
