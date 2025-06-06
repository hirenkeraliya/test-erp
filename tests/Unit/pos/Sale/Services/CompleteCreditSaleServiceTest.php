<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Services\CompleteCreditSaleService;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Models\BookingPayment;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Country;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\GiftCard;
use App\Models\Location;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Sale;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->data = [
        'happened_at' => '2022-01-04 04:20:50',
        'payments' => [
            [
                'type_id' => 1,
                'amount' => 10,
            ],
        ],
    ];

    $this->company = Company::factory()->make([
        'default_country_id' => 1,
    ]);
    $this->country = Country::factory()->make([
        'id' => 1,
    ]);
    $this->currency = Currency::factory()->make([
        'id' => 1,
        'country_id' => $this->country->id,
        'name' => 'Malaysian Ringgit',
        'code' => 'MYR',
    ]);
    $this->currencyRate = CurrencyRate::factory()->make([
        'id' => 1,
        'currency_id' => $this->currency->id,
        'rate' => 1,
    ]);

    $this->completeCreditSaleService = new CompleteCreditSaleService();
});

test('checkRequestDetails method throws an exception when sale is not credit sale', function (): void {
    $mock = $this->createPartialMock(CompleteCreditSaleService::class, ['checkPaymentCurrency']);
    $companyId = 1;
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);

    $sale->counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
    ]);

    $sale->counterUpdate->counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
    ]);

    $sale->counterUpdate->counter->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'country_id' => 1,
    ]);

    $completeCreditSaleData = new CompleteCreditSaleData(...$this->data);

    $mock->expects($this->once())
            ->method('checkPaymentCurrency');

    $mock->checkRequestDetails($completeCreditSaleData, $sale, collect([]), $companyId, 1);
})->throws(HttpException::class, 'The specified sale is not a credit sale.');

test(
    'checkRequestDetails method throws an exception when Payments exceeding the pending credit amount',
    function (): void {
        $mock = $this->createPartialMock(CompleteCreditSaleService::class, ['checkPaymentCurrency']);
        $companyId = 1;
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
            'credit_pending_amount' => 8,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $sale->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $sale->counterUpdate->counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
        ]);

        $sale->counterUpdate->counter->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $completeCreditSaleData = new CompleteCreditSaleData(...$this->data);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $mock->checkRequestDetails($completeCreditSaleData, $sale, collect([]), $companyId, 1);
    }
)->throws(HttpException::class, 'Payments exceeding the pending credit amount are not permitted.');

test('checkRequestDetails method cell same class method', function (): void {
    $companyId = 1;
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 100,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
    ]);

    $sale->counterUpdate->counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
    ]);

    $sale->counterUpdate->counter->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'country_id' => 1,
    ]);

    $mock = $this->createPartialMock(
        CompleteCreditSaleService::class,
        ['checkLoyaltyPoint', 'checkCreditNoteDetails', 'checkBookingPayment', 'checkGiftCard', 'checkPaymentCurrency']
    );

    $mock->expects($this->once())
        ->method('checkLoyaltyPoint');

    $mock->expects($this->once())
        ->method('checkCreditNoteDetails');

    $mock->expects($this->once())
        ->method('checkBookingPayment');

    $mock->expects($this->once())
        ->method('checkGiftCard');

    $mock->expects($this->once())
        ->method('checkPaymentCurrency');

    $data = [
        'happened_at' => '2022-01-04 04:20:50',
        'payments' => [
            [
                'type_id' => 1,
                'amount' => 10,
            ],
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 10,
            ],
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'amount' => 10,
            ],
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'amount' => 10,
            ],
            [
                'type_id' => StaticPaymentTypes::GIFT_CARD->value,
                'amount' => 10,
            ],
        ],
    ];

    $completeCreditSaleData = new CompleteCreditSaleData(...$data);
    $mock->checkRequestDetails($completeCreditSaleData, $sale, collect([]), $companyId, 1);
});

test('checkLoyaltyPoint method throws an exception when sale user not set', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);
    $sale->member = null;

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => 10,
        ],
    ]);

    $this->completeCreditSaleService->checkLoyaltyPoint($sale, $payment, collect([]));
})->throws(HttpException::class, 'To pay with loyalty points, a user account is required.');

test('checkLoyaltyPoint method throws an exception when sale user membership id not set', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => 10,
        ],
    ]);

    $this->completeCreditSaleService->checkLoyaltyPoint($sale, $payment, collect([]));
})->throws(HttpException::class, 'To redeem loyalty points, a membership must be associated with your user account.');

test('checkLoyaltyPoint method throws an exception when loyalty_points not set', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => 10,
        ],
    ]);

    $this->completeCreditSaleService->checkLoyaltyPoint($sale, $payment, collect([]));
})->throws(
    HttpException::class,
    'To ensure successful processing of the payment, it is necessary to provide a valid loyalty point value since loyalty points are the selected payment method.'
);

test(
    'checkLoyaltyPoint method throws an exception when loyalty_points more then user loyalty points set',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
            'credit_pending_amount' => 8,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $sale->member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
            'loyalty_points' => 9,
        ]);

        $payment = collect([
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 10,
                'loyalty_points' => 10,
            ],
        ]);

        $this->completeCreditSaleService->checkLoyaltyPoint($sale, $payment, collect([]));
    }
)->throws(
    HttpException::class,
    'The loyalty points you are trying to use exceed the balance available in your account.'
);

test(
    'checkLoyaltyPoint method throws an exception when loyalty_points amount and payment amount not match',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 169,
            'credit_pending_amount' => 10,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $sale->member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
            'loyalty_points' => 201,
        ]);

        $sale->member->membership = Membership::factory()->make([
            'company_id' => 1,
            'loyalty_points_per_currency_unit' => 1,
            'min_loyalty_points_for_redemption' => 200,
            'max_loyalty_points_for_redemption' => 40000,
        ]);

        $payment = collect([
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 100,
                'loyalty_points' => 201,
            ],
        ]);

        $this->completeCreditSaleService->checkLoyaltyPoint($sale, $payment, collect([]));
    }
)->throws(
    HttpException::class,
    'The amount you are trying to use, 100, exceeds the maximum amount that can be redeemed from your loyalty points, 201 according to your membership.'
);

test('checkLoyaltyPoint method return null when all check is true', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 69,
        'credit_pending_amount' => 100,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 400,
    ]);

    $sale->member->membership = Membership::factory()->make([
        'company_id' => 1,
        'loyalty_points_per_currency_unit' => 2,
        'min_loyalty_points_for_redemption' => 200,
        'max_loyalty_points_for_redemption' => 40000,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => 100,
            'loyalty_points' => 200,
        ],
    ]);

    $response = $this->completeCreditSaleService->checkLoyaltyPoint($sale, $payment, collect([]));

    $this->assertNull($response);
});

test(
    'checkCreditNoteDetails method throws an exception when loyalty_points more then user loyalty points set',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
            'credit_pending_amount' => 8,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $sale->member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
            'loyalty_points' => 9,
        ]);

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $payment = collect([
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 10,
                'loyalty_points' => 10,
            ],
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'amount' => 10,
            ],
        ]);

        $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
    }
)->throws(
    HttpException::class,
    'When using credit notes as a payment method, providing a valid credit note ID is mandatory. Without this information, the process cannot be completed as it serves as a crucial element in processing a credit note-based payment.'
);

test('checkCreditNoteDetails method throws an exception when credit not expire', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 9,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'amount' => 10,
            'credit_note_id' => 1,
        ],
    ]);

    $creditNote = CreditNote::factory()->make([
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'expiry_date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
    });

    $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
})->throws(
    HttpException::class,
    'We apologize, but the credit note you are attempting to use has expired and is no longer valid. Please contact customer support for further assistance.'
);

test('checkCreditNoteDetails method throws an exception when credit not not active', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 9,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'amount' => 10,
            'credit_note_id' => 1,
        ],
    ]);

    $creditNote = CreditNote::factory()->make([
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => CreditNoteStatuses::EXPIRED->value,
    ]);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
    });

    $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
})->throws(HttpException::class, 'This credit note is currently inactive and cannot be used for transactions.');

test('checkCreditNoteDetails method throws an exception when credit user and sale user not match', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 9,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'amount' => 10,
            'credit_note_id' => 1,
        ],
    ]);

    $creditNote = CreditNote::factory()->make([
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 2,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
    });

    $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
})->throws(HttpException::class, 'The designated user is currently unable to utilize the provided credit note.');

test(
    'checkCreditNoteDetails method throws an exception when credit note available_amount less then pass amount',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
            'credit_pending_amount' => 8,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
            'loyalty_points' => 9,
        ]);

        $payment = collect([
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'amount' => 10,
                'credit_note_id' => 1,
            ],
        ]);

        $creditNote = CreditNote::factory()->make([
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'expiry_date' => now()->addDay()->format('Y-m-d'),
            'status' => CreditNoteStatuses::ACTIVE->value,
            'available_amount' => 5,
        ]);

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
            $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
                ->times(1)
                ->andReturn(new Currency([
                    'symbol' => 'RS',
                ]));
        });

        $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
    }
)->throws(HttpException::class);

test('checkCreditNoteDetails method throws an exception when credit note is deferent companies', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 9,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'amount' => 10,
            'credit_note_id' => 1,
        ],
    ]);

    $creditNote = CreditNote::factory()->make([
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => CreditNoteStatuses::ACTIVE->value,
        'available_amount' => 15,
        'counter_update_id' => 1,
    ]);

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn(2);
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
})->throws(HttpException::class, 'It is not permitted to use credit notes from multiple companies');

test('checkCreditNoteDetails method return null when all check pass', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 8,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 9,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'amount' => 10,
            'credit_note_id' => 1,
        ],
    ]);

    $creditNote = CreditNote::factory()->make([
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => CreditNoteStatuses::ACTIVE->value,
        'available_amount' => 15,
        'counter_update_id' => 1,
    ]);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $response = $this->completeCreditSaleService->checkCreditNoteDetails($sale, $payment, collect([]), 1);
    $this->assertNull($response);
});

test('checkGiftCard method throws an exception when gift_card_id not pass', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'amount' => 10,
            'loyalty_points' => 10,
        ],
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
        ],
    ]);

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(
    HttpException::class,
    'Please ensure you enter a valid Gift Card ID when choosing Gift Card as the payment method.'
);

test('checkGiftCard method throws an exception when gift cart is null', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $this->mock(GiftCardQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn(null);
    });

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(HttpException::class, 'Unfortunately, we couldn`t find records of some of the gift cards you requested.');

test('checkGiftCard method throws an exception when gift cart is expiry', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $giftCard = GiftCard::factory()->make([
        'company_id' => 1,
        'number' => 1,
        'expiry_date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
    });

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(
    HttpException::class,
    'The payment was made using an expired gift card (Number: [1]). Please use a valid gift card to complete your transaction.'
);

test('checkGiftCard method throws an exception when gift cart is used', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $giftCard = GiftCard::factory()->make([
        'company_id' => 1,
        'number' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => GiftCardStatuses::USED->value,
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
    });

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(HttpException::class, 'The gift card with number 1 can only be used once.');

test('checkGiftCard method throws an exception when gift cart is in active', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $giftCard = GiftCard::factory()->make([
        'company_id' => 1,
        'number' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => GiftCardStatuses::EXPIRED->value,
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
    });

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(HttpException::class, 'The gift card with (number - [1]) is not active.');

test('checkGiftCard method throws an exception when gift cart amount is less then pass amount', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $giftCard = GiftCard::factory()->make([
        'company_id' => 1,
        'number' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => GiftCardStatuses::ACTIVE->value,
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        'available_amount' => 5,
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
    });

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(
    HttpException::class,
    'The requested payment amount of 10 exceeds the available amount of the gift card (number - [1]) , which is 5.'
);

test('checkGiftCard method throws an exception when gift cart company is deferent', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $giftCard = GiftCard::factory()->make([
        'company_id' => 2,
        'number' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => GiftCardStatuses::ACTIVE->value,
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        'available_amount' => 15,
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
    });

    $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
})->throws(HttpException::class, 'You cannot use a gift card from a different company.');

test('checkGiftCard method return null when all check pass', function (): void {
    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
            'gift_card_id' => 1,
        ],
    ]);

    $giftCard = GiftCard::factory()->make([
        'company_id' => 1,
        'number' => 1,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'status' => GiftCardStatuses::ACTIVE->value,
        'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        'available_amount' => 15,
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
    });

    $response = $this->completeCreditSaleService->checkGiftCard($payment, collect([]), 1);
    $this->assertNull($response);
});

test('checkBookingPayment method throws an exception when booking_payment_id not pass', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $sale->member = null;

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::GIFT_CARD->value,
            'amount' => 10,
        ],
        [
            'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'amount' => 10,
        ],
    ]);

    $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
})->throws(HttpException::class, 'Please provide the Booking Payment ID when selecting the Booking Payment option.');

test('checkBookingPayment method throws an exception when booking_payment not active', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $sale->member = null;

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'amount' => 10,
            'booking_payment_id' => 1,
        ],
    ]);

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => BookingPaymentStatuses::USED->value,
    ]);

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
    });

    $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
})->throws(HttpException::class, 'Sorry, booking payment is currently inactive.');

test(
    'checkBookingPayment method throws an exception when selected member does not match the member associated',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);
        $sale->member = Member::factory()->make([
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => null,
        ]);

        $payment = collect([
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'amount' => 10,
                'booking_payment_id' => 1,
            ],
        ]);

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
        });

        $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
    }
)->throws(
    HttpException::class,
    'The selected member does not match the member associated with the payment for the booking.'
);

test('checkBookingPayment method throws an exception when payment type id not pass', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);

    $payment = collect([
        [
            'type_id' => null,
            'amount' => 10,
            'booking_payment_id' => 1,
        ],
    ]);

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
    });

    $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
})->throws(HttpException::class, 'Please provide the Booking Payment type along with the Booking Payment ID.');

test('checkBookingPayment method throws an exception when booking payment is deferent company', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'amount' => 10,
            'booking_payment_id' => 1,
        ],
    ]);

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn(2);
    });

    $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
})->throws(HttpException::class, 'Sorry, you can`t mix bookings from different companies.');

test(
    'checkBookingPayment method throws an exception when booking payment amount is lass then pass amount',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 10,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);
        $sale->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => null,
        ]);

        $payment = collect([
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'amount' => 10,
                'booking_payment_id' => 1,
            ],
        ]);

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->value,
            'available_amount' => 5,
        ]);

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
        });

        $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
    }
)->throws(
    HttpException::class,
    'The requested payment amount of 10 exceeds the available booking payment balance of 5'
);

test('checkBookingPayment method return null when all check pass', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);

    $payment = collect([
        [
            'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'amount' => 10,
            'booking_payment_id' => 1,
        ],
    ]);

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => BookingPaymentStatuses::ACTIVE->value,
        'available_amount' => 15,
    ]);

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn(1);
    });

    $response = $this->completeCreditSaleService->checkBookingPayment($sale, $payment, collect([]), 1, 1);
    $this->assertNull($response);
});

test('saveDetails method cell same class method', function (): void {
    $companyId = 1;
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 100,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $sale->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);

    $mock = $this->createPartialMock(CompleteCreditSaleService::class, ['saveSaleMismatches']);

    $mock->expects($this->once())
        ->method('saveSaleMismatches');

    $data = [
        'happened_at' => '2022-01-04 04:20:50',
        'payments' => [
            [
                'type_id' => 1,
                'amount' => 10,
            ],
            [
                'type_id' => StaticPaymentTypes::LOYALTY_POINT->value,
                'amount' => 10,
                'loyalty_points' => 100,
            ],
            [
                'type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
                'amount' => 10,
                'credit_note_id' => 1,
            ],
            [
                'type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
                'amount' => 10,
                'booking_payment_id' => 1,
            ],
            [
                'type_id' => StaticPaymentTypes::GIFT_CARD->value,
                'amount' => 10,
                'gift_card_id' => 1,
            ],
        ],
    ];

    $this->mock(SalePaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(5)
            ->andReturn(1);
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $creditNote = CreditNote::factory()->make([
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'expiry_date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNote): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($creditNote);
        $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
            ->once();
    });

    $this->mock(CreditNoteUseQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
        'status' => BookingPaymentStatuses::USED->value,
    ]);

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($bookingPayment);
        $mock->shouldReceive('markAsUsed')
            ->once();
    });

    $this->mock(BookingPaymentUseQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $giftCard = GiftCard::factory()->make([
        'company_id' => 1,
        'number' => 1,
        'expiry_date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->mock(GiftCardQueries::class, function ($mock) use ($giftCard): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($giftCard);
        $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
            ->once();
    });

    $this->mock(GiftCardTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $completeCreditSaleData = new CompleteCreditSaleData(...$data);
    $mock->saveDetails($completeCreditSaleData, $sale, collect($data['payments']), collect([]), 1, $companyId, 1);
});

test('saveSaleMismatches method cell addNew method of PosMismatchQueries class', function (): void {
    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
        'total_amount_paid' => 10,
        'credit_pending_amount' => 100,
        'status' => SaleStatus::PENDING_CREDIT_SALE->value,
    ]);

    $this->mock(PosMismatchQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(3);
    });

    $saleMismatches = collect(['Test 1', 'Test 2', 'Test 3']);

    $this->completeCreditSaleService->saveSaleMismatches($sale, $saleMismatches);
});

test(
    'checkDeferentStore method call return null when same location complete sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100.00,
        ]);

        $sale->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $sale->counterUpdate->counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $response = $this->completeCreditSaleService->checkDeferentStore($location->id, $sale, collect([]));
        $this->assertNull($response);
    }
);

test(
    'checkDeferentStore method throws an exception when deferent location complete sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'total_price_paid' => 100.00,
        ]);

        $sale->counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => 1,
            'cashier_id' => 1,
        ]);

        $sale->counterUpdate->counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 2,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $response = $this->completeCreditSaleService->checkDeferentStore($location->id, $sale, collect([]));
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Credit sale cannot be completed at a different location.');

test('it calls the checkPaymentCurrency method currency id is not available in company', function (): void {
    $companyId = 1;
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->andReturn($this->company);
    });
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $saleDetails = $this->data;
    $saleDetails['payments'] = [
        [
            'type_id' => 1,
            'amount' => 10,
            'currency_id' => 2,
            'current_currency_rate' => 1,
            'currency_amount' => 10,
        ],
    ];
    $this->completeCreditSaleService->saleData = new CompleteCreditSaleData(...$saleDetails);

    $mismatches = collect([]);
    $this->completeCreditSaleService->checkPaymentCurrency(collect($saleDetails['payments']), $mismatches, $companyId);
})->throws(HttpException::class, 'Payment currency id 2 is not available in this company.');

test('it calls the checkPaymentCurrency method currency rate is not available in company', function (): void {
    $companyId = 1;
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->andReturn($this->company);
    });
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $saleDetails = $this->data;
    $saleDetails['payments'] = [
        [
            'type_id' => 1,
            'amount' => 10,
            'currency_id' => 1,
            'current_currency_rate' => 2,
            'currency_amount' => 10,
        ],
    ];
    $this->completeCreditSaleService->saleData = new CompleteCreditSaleData(...$saleDetails);

    $mismatches = collect([]);
    $this->completeCreditSaleService->checkPaymentCurrency(collect($saleDetails['payments']), $mismatches, $companyId);
})->throws(
    HttpException::class,
    'Payment currency rate 2 does not match with the actual currency rate of 1 for the currency id 1'
);

test('it calls the checkPaymentCurrency method currency amount is not matching', function (): void {
    $companyId = 1;
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getConfigurationColumnsById')
            ->once()
            ->andReturn($this->company);
    });
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $saleDetails = $this->data;
    $saleDetails['payments'] = [
        [
            'type_id' => 1,
            'amount' => 10,
            'currency_id' => 1,
            'current_currency_rate' => 1,
            'currency_amount' => 20,
        ],
    ];
    $this->completeCreditSaleService->saleData = new CompleteCreditSaleData(...$saleDetails);

    $mismatches = collect([]);
    $this->completeCreditSaleService->checkPaymentCurrency(collect($saleDetails['payments']), $mismatches, $companyId);
})->throws(HttpException::class, 'Payment amount 10 does not match with the actual currency amount of 20.');
