<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CloseCounterDataForStoreManager;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\CounterUpdate\Services\CounterUpdateDeclarationAttemptService;
use App\Domains\Denomination\DenominationQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Http\Controllers\StoreManager\DayCloseController;
use App\Models\Cashier;
use App\Models\CloseCounterDenomination;
use App\Models\CloseCounterPayment;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Denomination;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\StoreDayClose;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the getByIdFilterByStore method of the counterUpdateQueries queries class and returns proper response',
    function (): void {
        $locationId = 1;
        $companyId = 1;

        setStoreManagerStoreIdInSession($locationId);
        setStoreManagerStoreCompanyIdInSession($companyId);

        $denomination = Denomination::factory()->make([
            'company_id' => $companyId,
            'denomination' => 20,
        ]);

        $denominationData = [
            'company_id' => $companyId,
            'denomination' => $denomination->denomination,
            'quantity' => 0,
        ];

        $this->mock(DenominationQueries::class, function ($mock) use ($companyId, $denomination): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->with($companyId)
                ->andReturn(collect([$denomination]));
        });

        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdFilterByStore')
                ->once()
                ->andReturn(new CounterUpdate([]));
        });

        $this->mock(CloseCounterService::class, function ($mock): void {
            $mock->shouldReceive('prepareAndReturnCounterClosingDetails')
                ->once();
        });

        $dayCloseController = new DayCloseController($counterUpdateQueries);

        $response = $dayCloseController->counterClosingDetails(1);

        $this->assertEquals([
            'closed_at' => 'N/A',
            'mismatch_amount' => 0.0,
            'amount_mismatch_reason' => null,
            'denominations' => [$denominationData],
        ], $response['counter_closing_details']);
    }
);

test(
    'It calls the getByIdWithRelationsFilterByStore method of the counterUpdateQueries queries class and returns proper response',
    function (): void {
        $locationId = 1;
        $companyId = 1;

        setStoreManagerStoreIdInSession($locationId);
        setStoreManagerStoreCompanyIdInSession($companyId);

        $denomination = Denomination::factory()->make([
            'company_id' => $companyId,
            'denomination' => 20,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
            'closed_at' => Carbon::now(),
        ]);

        $closeCounterDenomination = CloseCounterDenomination::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
        ]);

        $counterUpdate->denominations = collect([$closeCounterDenomination]);

        $counterUpdatePayment = CloseCounterPayment::factory()->make([
            'counter_update_id' => 1,
            'payment_type_id' => 1,
        ]);

        $counterUpdatePayment->paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $counterUpdate->payments = collect([$counterUpdatePayment]);

        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use (
            $counterUpdate
        ): void {
            $mock->shouldReceive('getByIdFilterByStore')
                ->once()
                ->andReturn($counterUpdate);
            $mock->shouldReceive('getByIdWithRelationsFilterByStore')
                ->once()
                ->andReturn($counterUpdate);
        });

        $this->mock(DenominationQueries::class, function ($mock) use ($companyId, $denomination): void {
            $mock->shouldReceive('getByCompanyId')
                ->once()
                ->with($companyId)
                ->andReturn(collect([$denomination]));
        });

        $dayCloseController = new DayCloseController($counterUpdateQueries);

        $response = $dayCloseController->counterClosingDetails(1);

        expect($response['counter_closing_details'])
        ->toHaveKeys(
            [
                'opening_balance',
                'closing_balance',
                'mismatch_amount',
                'amount_mismatch_reason',
                'total_sales',
                'total_sales_amount',
                'total_layaway_sales',
                'total_layaway_sales_amount',
                'total_voided_sales',
                'total_voided_sales_amount',
                'total_item_wise_discount_amount',
                'total_cart_wide_discount_amount',
                'total_discount_amount',
                'total_sales_round_off',
                'total_sale_returns',
                'total_sale_returns_amount',
                'total_credit_notes_used_amount',
                'total_credit_notes_refunded_amount',
                'total_sale_returns_round_off',
                'total_cashback',
                'total_cashback_amount',
                'total_vouchers_used',
                'total_vouchers_generated',
                'total_booking_payment_amount',
                'total_booking_payment_refunded_amount',
                'total_booking_payment_used_amount',
                'total_cash_ins_amount',
                'total_cash_outs_amount',
                'total_cash_amount_in_sales',
                'total_cash_amount_in_booking_payment',
                'total_cash_amount_in_booking_payment_refunded',
                'payments',
            ]
        );
    }
);

test('closeCounter method calls and returns proper response', function (): void {
    $locationId = 1;

    setStoreManagerStoreIdInSession($locationId);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $preparedArray = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
    ];

    $denomination = [
        'denomination' => 100,
        'quantity' => 1,
    ];

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);
    $closeCounterData = new CloseCounterDataForStoreManager(...$preparedArray);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
        'closing_balance' => null,
        'closed_at' => null,
    ]);

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
        $mock->shouldReceive('getByIdFilterByStore')
            ->once()
            ->andReturn($counterUpdate);
    });

    $this->mock(CloseCounterService::class, function ($mock): void {
        $mock->shouldReceive('prepareAndReturnCounterClosingDetails')
            ->once();
        $mock->shouldReceive('checkRequestDetails')
            ->once();
        $mock->shouldReceive('closeCounter')
            ->once();
    });

    $this->mock(CounterQueries::class, function ($mock) use ($counterUpdate): void {
        $mock->shouldReceive('getByCounterUpdateId')
            ->once();
        $mock->shouldReceive('unsetCounterUpdateId')
            ->once()
            ->andReturn($counterUpdate);
    });

    $this->mock(CashierQueries::class, function ($mock) use ($counterUpdate, $cashier): void {
        $mock->shouldReceive('getByCounterUpdateId')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('unsetCounterUpdateId')
            ->once()
            ->andReturn($counterUpdate);
    });

    $this->mock(CounterUpdateDeclarationAttemptService::class, function ($mock): void {
        $mock->shouldReceive('getDeclarationAttemptPayments')
            ->once()
            ->andReturn(collect([]));

        $mock->shouldReceive('saveDeclarationAttemptDetails')
            ->once();
    });

    $dayCloseController = new DayCloseController($counterUpdateQueries);
    $dayCloseController->closeCounter($closeCounterData, 1, $request);
});

test('dayClose method throws an exception when counters are still open while day close', function (): void {
    $locationId = 1;

    setStoreManagerStoreIdInSession($locationId);

    $request = new Request();

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdWithReceiptFooterDisclaimerAndCreatedAt')
            ->once()
            ->andReturn($location);
    });

    $this->mock(StoreDayCloseQueries::class, function ($mock): void {
        $mock->shouldReceive('getLastDayClose')
            ->once();
    });

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getOpenCountersCountFilterByStoreAndDates')
            ->once()
            ->andReturn(1);
    });

    $dayCloseController = new DayCloseController($counterUpdateQueries);

    $dayCloseController->dayClose($request);
})->throws(HttpException::class, '1 counters are still open. Please close all the counters for Day Close first.');

test('dayClose method calls respective methods of queries class and returns proper response', function (): void {
    $locationId = 1;

    setStoreManagerStoreIdInSession($locationId);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
        'closing_balance' => null,
        'closed_at' => null,
    ]);

    $closeCounterPayment = CloseCounterPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'payment_type_id' => 1,
        'total_transactions' => 1,
        'total_amount' => 1,
    ]);

    $counterUpdate->payments = collect([$closeCounterPayment]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getByIdWithReceiptFooterDisclaimerAndCreatedAt')
            ->once()
            ->andReturn($location);
    });

    $this->mock(StoreDayCloseQueries::class, function ($mock): void {
        $mock->shouldReceive('getLastDayClose')
            ->once();
        $mock->shouldReceive('loadRelations')
            ->once();
    });

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getOpenCountersCountFilterByStoreAndDates')
            ->once();
    });

    $this->mock(StoreDayCloseService::class, function ($mock): void {
        $mock->shouldReceive('addStoreDayClose')
            ->once();
    });

    $dayCloseController = new DayCloseController($counterUpdateQueries);

    $dayCloseController->dayClose($request);
});

test('It calls the exportDayClose method and returns a proper response', function (): void {
    $locationId = 1;
    $companyId = 1;

    setStoreManagerStoreIdInSession($locationId);
    setStoreManagerStoreCompanyIdInSession($companyId);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
        'closing_balance' => null,
        'closed_at' => Carbon::now(),
    ]);

    $counterUpdate->counter = $counter;

    $storeDayClose = StoreDayClose::factory()->make([
        'id' => 1,
        'location_id' => $location->id,
        'closed_by_store_manager_id' => $storeManager->id,
    ]);

    $storeDayCloseQueries = $this->mock(StoreDayCloseQueries::class, function ($mock) use ($storeDayClose): void {
        $mock->shouldReceive('getLastDayClose')
            ->once()
            ->andReturn($storeDayClose);
    });

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use ($counterUpdate): void {
        $mock->shouldReceive('getByDayCloseAndStore')
        ->andReturn(collect([$counterUpdate]))
            ->once();
    });

    $dayCloseController = new DayCloseController($counterUpdateQueries);
    $response = $dayCloseController->exportDayClose('filename.csv');

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
