<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNote\Services\CheckCreditNoteRefundRequestService;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\Company;
use App\Models\CounterUpdate;
use App\Models\Country;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Location;
use App\Models\PaymentType;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
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
});

test(
    'checkRequestDetails method sets Mismatches when credit note & cashier company id mismatch',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            100,
            1,
            true,
            2
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->never()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
               ->never()
                ->andReturn(true);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');
        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, 'You cannot refund different company credit note.');

test(
    'checkRequestDetails method sets Mismatches when credit note status is used',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::USED->value,
            100
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->never()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->never()
                ->andReturn(true);
        });

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->creditNoteMismatches = collect([]);
        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, 'Used Credit note cannot be refunded.');

test(
    'checkRequestDetails method sets Mismatches when credit note status is expired',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::EXPIRED->value,
            100
        );
        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->never()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->never()
                ->andReturn(true);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);
        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $mock->creditNoteMismatches = collect([]);
        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, 'Expired Credit note cannot be refunded.');

test(
    'checkRequestDetails method sets Mismatches when credit note refund amount mismatched',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            10
        );
        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);
        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->never()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->never()
                ->andReturn(true);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');
        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(
    HttpException::class,
    'Only the full amount can be refunded. Requested amount is: 100. But expected amount is: 10'
);

test(
    'checkRequestDetails method sets Mismatches when credit note refund payment type id is credit note',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            100,
            StaticPaymentTypes::CREDIT_NOTE->value
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->never()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->never()
                ->andReturn(true);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);
        $mock->expects($this->once())
            ->method('checkPaymentCurrency');

        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, 'Credit Note refund payment type cannot be credit note.');

test(
    'checkRequestDetails method sets Mismatches when credit note refund payment type id is booking payment',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            100,
            StaticPaymentTypes::BOOKING_PAYMENT->value
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->never()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->never()
                ->andReturn(true);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');
        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, 'Credit Note refund payment type cannot be booking payment.');

test(
    'checkRequestDetails method sets Mismatches when payment type is not available for refund',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            100,
            1,
            false
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->never()
                ->andReturn(true);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');
        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, 'Only refund payment types are allowed for refund.');

test(
    'checkRequestDetails method sets Mismatches when the specified store manager is not available in our records.',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            100
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->once()
                ->andReturn(false);
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');
        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
)->throws(HttpException::class, "Only currently opened counter's store manager is allowed for credit note refund.");

test(
    'checkRequestDetails method call checkStoreManagerAuthorizationCode method of StoreManagerAuthorizationCodeUsageService class.',
    function (): void {
        [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType] = commonCreditNoteRefundRecord(
            CreditNoteStatuses::ACTIVE->value,
            100
        );

        $mock = $this->createPartialMock(CheckCreditNoteRefundRequestService::class, ['checkPaymentCurrency']);

        $this->mock(LocationQueries::class, function ($mock) use ($locationOne, $locationTwo): void {
            $mock->shouldReceive('getLocationByCountersCounterUpdateId')
                ->once()
                ->andReturn($locationOne);
            $mock->shouldReceive('getStoreByCounters')
                ->once()
                ->andReturn($locationTwo);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($paymentType);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdStoreIdAndPasscode')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
        });

        $creditNoteRefundData = new CreditNoteRefundData(...$preparedArray);

        $mock->expects($this->once())
            ->method('checkPaymentCurrency');
        $mock->creditNoteMismatches = collect([]);

        $mock->checkRequestDetails($creditNoteRefundData, $creditNote, 1);
    }
);

test(
    'checkStoreManagerAuthorizationCode method call checkStoreManagerAuthorizationCode method of StoreManagerAuthorizationCodeUsageService class.',
    function (): void {
        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
        });

        $creditNoteRefundData = new CreditNoteRefundData(...[
            'payment_type_id' => 1,
            'amount' => 100.00,
            'currency_id' => 1,
            'current_currency_rate' => 1,
            'currency_amount' => 100.00,
            'passcode' => '123456',
            'store_manager_id' => 1,
        ]);

        $checkCreditNoteRefundRequestService = new CheckCreditNoteRefundRequestService();
        $checkCreditNoteRefundRequestService->creditNoteMismatches = collect([]);

        $checkCreditNoteRefundRequestService->checkStoreManagerAuthorizationCode($creditNoteRefundData);
    }
);

test('it calls the checkPaymentCurrency method currency id is not available in company', function (): void {
    $creditNoteRefundData = new CreditNoteRefundData(...[
        'payment_type_id' => 1,
        'amount' => 100.00,
        'currency_id' => 2,
        'current_currency_rate' => 1,
        'currency_amount' => 100.00,
        'passcode' => '123456',
        'store_manager_id' => 1,
    ]);

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

    $checkCreditNoteRefundRequestService = new CheckCreditNoteRefundRequestService();
    $checkCreditNoteRefundRequestService->creditNoteMismatches = collect([]);

    $checkCreditNoteRefundRequestService->checkPaymentCurrency($creditNoteRefundData, 1);
})->throws(HttpException::class, 'Payment currency id 2 is not available in this company.');

test('it calls the checkPaymentCurrency method currency rate is not available in company', function (): void {
    $creditNoteRefundData = new CreditNoteRefundData(...[
        'payment_type_id' => 1,
        'amount' => 100.00,
        'currency_id' => 1,
        'current_currency_rate' => 2,
        'currency_amount' => 100.00,
        'passcode' => '123456',
        'store_manager_id' => 1,
    ]);

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

    $checkCreditNoteRefundRequestService = new CheckCreditNoteRefundRequestService();
    $checkCreditNoteRefundRequestService->creditNoteMismatches = collect([]);

    $checkCreditNoteRefundRequestService->checkPaymentCurrency($creditNoteRefundData, 1);
})->throws(
    HttpException::class,
    'Payment currency rate 2 does not match with the actual currency rate of 1 for the currency id 1'
);

test('it calls the checkPaymentCurrency method currency amount is not matching', function (): void {
    $creditNoteRefundData = new CreditNoteRefundData(...[
        'payment_type_id' => 1,
        'amount' => 100.00,
        'currency_id' => 1,
        'current_currency_rate' => 1,
        'currency_amount' => 200.00,
        'passcode' => '123456',
        'store_manager_id' => 1,
    ]);

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

    $checkCreditNoteRefundRequestService = new CheckCreditNoteRefundRequestService();
    $checkCreditNoteRefundRequestService->creditNoteMismatches = collect([]);

    $checkCreditNoteRefundRequestService->checkPaymentCurrency($creditNoteRefundData, 1);
})->throws(HttpException::class, 'Payment amount 100 does not match with the actual currency amount of 200.');

function commonCreditNoteRefundRecord(
    int $status,
    int $availableAmount,
    int $paymentTypeId = 1,
    bool $availableForRefund = true,
    int $companyId = 1,
): array {
    $preparedArray = [
        'payment_type_id' => $paymentTypeId,
        'amount' => 100.00,
        'currency_id' => 1,
        'current_currency_rate' => 1,
        'currency_amount' => 100.00,
        'passcode' => '123456',
        'store_manager_id' => 1,
    ];

    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'available_amount' => $availableAmount,
        'status' => $status,
    ]);

    $creditNote->counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => 1,
        'cashier_id' => 1,
    ]);

    $locationOne = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $locationTwo = Location::factory()->make([
        'id' => 2,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'parent_payment_type_id' => null,
        'is_available_for_refund' => $availableForRefund,
        'status' => true,
    ]);

    return [$preparedArray, $creditNote, $locationOne, $locationTwo, $paymentType];
}
