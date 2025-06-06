<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CloseCounterDenomination\CloseCounterDenominationQueries;
use App\Domains\CloseCounterPayment\CloseCounterPaymentQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\DataObjects\CloseCounterData;
use App\Domains\Counter\DataObjects\CloseCounterDenominationData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Services\CloseCounterService;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\BookingPaymentRefund;
use App\Models\CashMovement;
use App\Models\CounterUpdate;
use App\Models\CreditNoteRefund;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\SaleCashback;
use App\Models\SaleDiscount;
use App\Models\SaleItem;
use App\Models\SaleItemDiscount;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'the prepareAndReturnCounterClosingDetails method calls the methods of queries class as expected.',
    function (): void {
        $paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'payment_one',
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $salePayment = SalePayment::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'payment_type_id' => $paymentType->id,
            'counter_update_id' => null,
        ]);

        $creditNoteRefund = CreditNoteRefund::factory()->make([
            'id' => 1,
            'credit_note_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'payment_type_id' => $paymentType->id,
            'store_manager_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);

        $layawaySale = Sale::factory()->make([
            'id' => 2,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::COMPLETE_LAYAWAY_SALE->value,
        ]);

        $voidSale = Sale::factory()->make([
            'id' => 3,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::VOID_SALE->value,
        ]);

        $cancelLayaWaySale = Sale::factory()->make([
            'id' => 3,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::CANCEL_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => $sale->id,
            'product_id' => 1,
            'derivative_id' => null,
        ]);

        $sale->saleItems = collect([$saleItem]);
        $salePayment->bookingPaymentUse = collect([]);
        $salePayment->creditNoteUse = collect([]);
        $salePayment->paymentType = $paymentType;

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 2,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $cashMovement = CashMovement::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'cash_movement_reason_id' => 1,
            'authorizer_id' => 1,
        ]);

        $bookingPaymentPayment = BookingPaymentPayment::factory()->make([
            'id' => 1,
            'booking_payment_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'payment_type_id' => $paymentType->id,
        ]);

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'member_id' => 1,
        ]);

        $saleDiscount = SaleDiscount::factory()->make([
            'id' => 1,
            'discountable_id' => 1,
            'discountable_type' => ModelMapping::PROMOTION->value,
            'sale_id' => $sale->id,
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => $saleItem->id,
            'discountable_id' => 1,
            'discountable_type' => ModelMapping::PROMOTION->value,
        ]);

        $bookingPaymentRefund = BookingPaymentRefund::factory()->make([
            'id' => 1,
            'booking_payment_id' => 1,
            'counter_update_id' => $counterUpdate->id,
            'payment_type_id' => $paymentType->id,
        ]);

        $saleCashback = SaleCashback::factory()->make([
            'sale_id' => 2,
            'cashback_id' => 1,
            'cash_movement_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use (
            $sale,
            $layawaySale,
            $voidSale,
            $cancelLayaWaySale
        ): void {
            $mock->shouldReceive('getRegularSalesByCounterUpdateId')
                ->once()
                ->andReturn(collect([$sale]));

            $mock->shouldReceive('getLayawaySalesByCounterUpdateId')
                ->once()
                ->andReturn(collect([$layawaySale]));

            $mock->shouldReceive('getCreditSalesByCounterUpdateId')
                ->once()
                ->andReturn(collect([$sale]));

            $mock->shouldReceive('getCancelLayawaySalesByCounterUpdateId')
                ->once()
                ->andReturn(collect([$cancelLayaWaySale]));

            $mock->shouldReceive('getVoidedSalesByCounterUpdateId')
                ->once()
                ->andReturn(collect([$voidSale]));

            $mock->shouldReceive('getSalesWithoutVoidSaleByCounterUpdateId')
                ->once()
                ->andReturn(collect([$sale]));
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getByCounterUpdateId')
                ->once()
                ->andReturn(collect([$saleReturn]));
        });

        $this->mock(SalePaymentQueries::class, function ($mock) use ($salePayment): void {
            $mock->shouldReceive('getByCounterUpdateIdWithRelations')
                ->once()
                ->andReturn(collect([$salePayment]));
        });

        $this->mock(CashMovementQueries::class, function ($mock) use ($cashMovement): void {
            $mock->shouldReceive('getByCounterUpdateId')
                ->once()
                ->andReturn(collect([$cashMovement]));
        });

        $this->mock(CreditNoteRefundQueries::class, function ($mock) use ($creditNoteRefund): void {
            $mock->shouldReceive('getByCounterUpdateIdWithPaymentType')
                ->once()
                ->andReturn(collect([$creditNoteRefund]));
        });

        $this->mock(BookingPaymentRefundQueries::class, function ($mock) use ($bookingPaymentRefund): void {
            $mock->shouldReceive('getByCounterUpdateIdWithPaymentType')
                ->once()
                ->andReturn(collect([$bookingPaymentRefund]));
        });

        $this->mock(BookingPaymentPaymentQueries::class, function ($mock) use ($bookingPaymentPayment): void {
            $mock->shouldReceive('getByCounterUpdateIdWithPaymentType')
                ->once()
                ->andReturn(collect([$bookingPaymentPayment]));
        });

        $this->mock(SaleDiscountQueries::class, function ($mock) use ($saleDiscount): void {
            $mock->shouldReceive('getSaleDiscountByCounterUpdateId')
                ->once()
                ->andReturn(collect([$saleDiscount]));
        });

        $this->mock(SaleItemDiscountQueries::class, function ($mock) use ($saleItemDiscount): void {
            $mock->shouldReceive('getSaleItemDiscountByCounterUpdateId')
                ->once()
                ->andReturn(collect([$saleItemDiscount]));
        });

        $this->mock(SaleCashbackQueries::class, function ($mock) use ($saleCashback): void {
            $mock->shouldReceive('getByCounterUpdateId')
                ->once()
                ->andReturn(collect([$saleCashback]));
        });

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('getCountByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('getBookingPaymentCountByCounterUpdateId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCounterUpdateId')
                ->once()
                ->andReturn(collect());
        });

        $this->mock(BookingPaymentUseQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCounterUpdateId')
                ->once()
                ->andReturn(collect());
        });

        $closeCounterService = new CloseCounterService();
        $closeCounterService->prepareAndReturnCounterClosingDetails($counterUpdate);
    }
);

test(
    'checkRequestDetails method throws an exception when the mismatch in specified closing amount  and reason is not specified.',
    function (): void {
        $counterClosingDetails = [];
        $preparedArray = [
            'closing_balance' => 200,
            'mismatch_amount_reason' => null,
            'closed_by_pos_at' => null,
        ];

        $denomination = [
            'denomination' => 100,
            'quantity' => 1,
        ];

        $counterClosingDetails['closing_balance'] = 100;

        $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);

        $closeCounterData = new CloseCounterData(...$preparedArray);

        $closeCounterService = new CloseCounterService();
        $closeCounterService->checkRequestDetails($closeCounterData, $counterClosingDetails, 417);
    }
)->throws(
    HttpException::class,
    'Reason field is required. Please specify the amount ' . 200 . ' does not match with the expected amount ' . 100
);

test(
    'checkRequestDetails method throws an exception when denominations calculation does not match with closing balance.',
    function (): void {
        $preparedArray = [
            'closing_balance' => 100,
            'mismatch_amount_reason' => null,
            'closed_by_pos_at' => null,
        ];

        $denomination = [
            'denomination' => 200,
            'quantity' => 1,
        ];

        $counterClosingDetails = [
            'closing_balance' => 100.0,
            'opening_balance' => 0,
            'total_cash_ins_amount' => 0,
            'total_cash_outs_amount' => 0,
            'payments' => collect([
                [
                    'payment_type_id' => 1,
                    'total' => 100,
                ],
            ]),
        ];

        $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);

        $closeCounterData = new CloseCounterData(...$preparedArray);

        $closeCounterService = new CloseCounterService();
        $closeCounterService->checkRequestDetails($closeCounterData, $counterClosingDetails, 412);
    }
)->throws(HttpException::class, 'The cash denomination does not match.');

test(
    'checkRequestDetails method throws an exception when denominations missing in request and closing balance more than zero.',
    function (): void {
        $preparedArray = [
            'closing_balance' => 100,
            'mismatch_amount_reason' => null,
            'closed_by_pos_at' => null,
        ];

        $counterClosingDetails = [
            'closing_balance' => 100.0,
            'opening_balance' => 0,
            'total_cash_ins_amount' => 0,
            'total_cash_outs_amount' => 0,
            'payments' => collect([
                [
                    'payment_type_id' => 1,
                    'total' => 100,
                ],
            ]),
        ];

        $preparedArray['denominations'] = null;

        $closeCounterData = new CloseCounterData(...$preparedArray);

        $closeCounterService = new CloseCounterService();
        $closeCounterService->checkRequestDetails($closeCounterData, $counterClosingDetails, 417);
    }
)->throws(HttpException::class, 'A denomination is required');

test('closeCounter method calls the respective queries class as expected.', function (): void {
    $preparedArray = [
        'closing_balance' => 100,
        'mismatch_amount_reason' => null,
        'closed_by_pos_at' => null,
    ];

    $denomination = [
        'denomination' => 200,
        'quantity' => 1,
    ];

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
    ]);

    $counterClosingDetails = [
        'closing_balance' => 100.0,
        'opening_balance' => 0,
        'total_cash_ins_amount' => 0,
        'total_cash_outs_amount' => 0,
        'payments' => collect([
            [
                'payment_type_id' => 1,
                'total' => 100,
            ],
        ]),
    ];

    $preparedArray['denominations'] = CloseCounterDenominationData::collection([$denomination]);

    $this->mock(CloseCounterDenominationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('closeCounterUpdate')
            ->once();
    });
    $this->mock(CloseCounterPaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $closeCounterData = new CloseCounterData(...$preparedArray);

    $closeCounterService = new CloseCounterService();
    $closeCounterService->closeCounter(
        $closeCounterData,
        $counterUpdate,
        $counterClosingDetails,
        ModelMapping::CASHIER->name,
        1
    );
});

test('preparePayments method returns sale payment type wise records with cash payment type', function (): void {
    $paymentTypeCash = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'parent_payment_type_id' => null,
        'name' => 'cash',
    ]);

    $paymentTypeCreditNote = PaymentType::factory()->make([
        'id' => 2,
        'company_id' => 1,
        'parent_payment_type_id' => null,
        'name' => 'booking_payment',
    ]);

    $paymentTypeBookingPayment = PaymentType::factory()->make([
        'id' => 3,
        'company_id' => 1,
        'parent_payment_type_id' => null,
        'name' => 'booking_payment',
    ]);

    $paymentTypeCreditCard = PaymentType::factory()->make([
        'id' => 100,
        'company_id' => 1,
        'parent_payment_type_id' => null,
        'name' => 'credit card',
    ]);

    $paymentTypeDebitCard = PaymentType::factory()->make([
        'id' => 101,
        'company_id' => 1,
        'parent_payment_type_id' => null,
        'name' => 'debit card',
    ]);

    $salePaymentInCash = SalePayment::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'payment_type_id' => $paymentTypeCash->id,
        'counter_update_id' => null,
        'amount' => 20,
    ]);

    $salePaymentInCash->paymentType = $paymentTypeCash;

    $salePaymentInBookingPayment = SalePayment::factory()->make([
        'id' => 2,
        'sale_id' => 2,
        'payment_type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
        'counter_update_id' => null,
        'amount' => 10,
    ]);

    $salePaymentInBookingPayment->paymentType = $paymentTypeBookingPayment;

    $salePaymentInCreditNotePayment = SalePayment::factory()->make([
        'id' => 3,
        'sale_id' => 3,
        'payment_type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
        'counter_update_id' => null,
        'amount' => 10,
    ]);

    $salePaymentInCreditNotePayment->paymentType = $paymentTypeCreditNote;

    $bookingPaymentPaymentOne = BookingPaymentPayment::factory()->make([
        'id' => 1,
        'booking_payment_id' => 1,
        'counter_update_id' => 1,
        'payment_type_id' => $paymentTypeCash->id,
        'amount' => 20,
    ]);

    $bookingPaymentPaymentOne->paymentType = $paymentTypeCash;

    $bookingPaymentRefund = BookingPaymentRefund::factory()->make([
        'id' => 1,
        'booking_payment_id' => 1,
        'counter_update_id' => 1,
        'payment_type_id' => $paymentTypeCash->id,
        'amount' => 10,
    ]);

    $bookingPaymentRefund->paymentType = $paymentTypeCash;

    $creditNoteRefundOne = CreditNoteRefund::factory()->make([
        'id' => 1,
        'credit_note_id' => 1,
        'counter_update_id' => 1,
        'payment_type_id' => $paymentTypeCash->id,
        'amount' => 10,
        'store_manager_id' => 1,
    ]);

    $bookingPaymentPaymentTwo = BookingPaymentPayment::factory()->make([
        'id' => 2,
        'booking_payment_id' => 2,
        'counter_update_id' => 1,
        'payment_type_id' => $paymentTypeCreditCard->id,
        'amount' => 10,
    ]);

    $bookingPaymentPaymentTwo->paymentType = $paymentTypeCreditCard;

    $creditNoteRefundTwo = CreditNoteRefund::factory()->make([
        'id' => 2,
        'credit_note_id' => 2,
        'counter_update_id' => 1,
        'payment_type_id' => $paymentTypeDebitCard->id,
        'amount' => 10,
        'store_manager_id' => 1,
    ]);

    $cashMovement = CashMovement::factory()->make([
        'offline_id' => 1,
        'counter_update_id' => 1,
        'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
        'cash_movement_reason_id' => 1,
        'authorizer_id' => 1,
        'authorizer_type' => ModelMapping::STORE_MANAGER->name,
        'amount' => 10,
    ]);

    $creditNoteRefundTwo->paymentType = $paymentTypeDebitCard;

    $creditNoteRefundOne->paymentType = $paymentTypeCash;

    $salePayments = collect([$salePaymentInCash, $salePaymentInBookingPayment, $salePaymentInCreditNotePayment]);

    $bookingPaymentPayments = collect([$bookingPaymentPaymentOne, $bookingPaymentPaymentTwo]);
    $bookingPaymentRefunds = collect([$bookingPaymentRefund]);
    $creditNotesRefunds = collect([$creditNoteRefundOne, $creditNoteRefundTwo]);

    $closeCounterService = new CloseCounterService();

    $response = $closeCounterService->preparePayments(
        $salePayments,
        $bookingPaymentPayments,
        $bookingPaymentRefunds,
        $creditNotesRefunds
    );

    $total = ($salePaymentInCash->amount + $bookingPaymentPaymentOne->amount) -
        ($bookingPaymentRefund->amount + $creditNoteRefundOne->amount);

    expect($response->toArray()[0])
        ->toHaveKey('payment_type_id', $paymentTypeCash->id)
        ->toHaveKey('total', $total)
        ->toHaveKey('total_transactions', 4);

    expect($response->toArray()[1])
        ->toHaveKey('payment_type_id', $salePaymentInBookingPayment->payment_type_id)
        ->toHaveKey('total', $salePaymentInBookingPayment->amount)
        ->toHaveKey('total_transactions', 1);

    expect($response->toArray()[2])
        ->toHaveKey('payment_type_id', $salePaymentInCreditNotePayment->payment_type_id)
        ->toHaveKey('total', $salePaymentInCreditNotePayment->amount)
        ->toHaveKey('total_transactions', 1);

    expect($response->toArray()[3])
        ->toHaveKey('payment_type_id', $bookingPaymentPaymentTwo->payment_type_id)
        ->toHaveKey('total', $bookingPaymentPaymentTwo->amount)
        ->toHaveKey('total_transactions', 1);

    expect($response->toArray()[4])
        ->toHaveKey('payment_type_id', $creditNoteRefundTwo->payment_type_id)
        ->toHaveKey('total', -$creditNoteRefundTwo->amount)
        ->toHaveKey('total_transactions', 1);
});

test(
    'preparePayments method returns sale payment type wise records without cash payment type & with cash IN',
    function (): void {
        $paymentTypeCreditCard = PaymentType::factory()->make([
            'id' => 100,
            'company_id' => 1,
            'parent_payment_type_id' => null,
            'name' => 'credit card',
        ]);

        $paymentTypeDebitCard = PaymentType::factory()->make([
            'id' => 101,
            'company_id' => 1,
            'parent_payment_type_id' => null,
            'name' => 'debit card',
        ]);

        $salePaymentInCreditNote = SalePayment::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'payment_type_id' => $paymentTypeCreditCard->id,
            'counter_update_id' => null,
            'amount' => 20,
        ]);

        $salePaymentInBookingPayment = SalePayment::factory()->make([
            'id' => 2,
            'sale_id' => 2,
            'payment_type_id' => $paymentTypeDebitCard->id,
            'counter_update_id' => null,
            'amount' => 10,
        ]);

        $salePaymentInCreditNote->paymentType = $paymentTypeCreditCard;
        $salePaymentInBookingPayment->paymentType = $paymentTypeDebitCard;

        $cashMovement = CashMovement::factory()->make([
            'offline_id' => 1,
            'counter_update_id' => 1,
            'cash_movement_type_id' => CashMovementTypes::CASH_IN->value,
            'cash_movement_reason_id' => 1,
            'authorizer_id' => 1,
            'authorizer_type' => ModelMapping::STORE_MANAGER->name,
            'amount' => 10,
        ]);

        $salePayments = collect([$salePaymentInCreditNote, $salePaymentInBookingPayment]);

        $bookingPaymentPayments = collect([]);
        $bookingPaymentRefunds = collect([]);
        $creditNotesRefunds = collect([]);

        $closeCounterService = new CloseCounterService();

        $response = $closeCounterService->preparePayments(
            $salePayments,
            $bookingPaymentPayments,
            $bookingPaymentRefunds,
            $creditNotesRefunds
        );

        expect($response->toArray()[0])
            ->toHaveKey('payment_type_id', $salePaymentInCreditNote->payment_type_id)
            ->toHaveKey('total', 20)
            ->toHaveKey('total_transactions', 1);

        expect($response->toArray()[1])
            ->toHaveKey('payment_type_id', $salePaymentInBookingPayment->payment_type_id)
            ->toHaveKey('total', 10)
            ->toHaveKey('total_transactions', 1);
    }
);
