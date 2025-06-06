<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\BookingPaymentRefund\Services\CheckBookingPaymentRequestDetailsService;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Company\Enums\BookingPaymentRefundTypes;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Models\BookingPayment;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Employee;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
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
    'checkOfflineId calls the doesOfflineIdExist method of BookingPaymentQueries class',
    function (): void {
        BookingPayment::factory()->make([
            'id' => 1,
            'offline_id' => 'a123',
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $this->mock(BookingPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineIdExist')
                ->once()
                ->andReturn(true);
        });

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();

        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $checkBookingPaymentRequestDetailsService->checkOfflineId('a123', 1);
    }
)->throws(HttpException::class, 'The selected offline ID is already in use.');

test(
    'checkBillReferenceNumber method set mismatches when company has set true and doest not specify in request.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $company = Company::factory()->make([
            'id' => 1,
            'is_bill_reference_number_mandatory' => true,
            'default_country_id' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $checkBookingPaymentRequestDetailsService->checkBillReferenceNumber(null, $company);
    }
)->throws(HttpException::class, 'Bill reference number is required while booking payment store.');

test(
    'checkStatusIsRefunded method set mismatches when booking payment status is already refunded.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
            'status' => BookingPaymentStatuses::REFUNDED->value,
        ]);

        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $checkBookingPaymentRequestDetailsService->checkStatusIsRefunded($bookingPayment->status);
    }
)->throws(HttpException::class, 'Booking Payment already refunded');

it(
    'checkPaymentType method set mismatches when the specified payment type is credit note',
    function (): void {
        $paymentType = PaymentType::factory()->make([
            'id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'company_id' => 1,
            'is_available_for_refund' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkPaymentType($paymentType->id);
    }
)->throws(HttpException::class, 'Payment type cannot be credit note payment.');

it(
    'checkPaymentType method set mismatches when the specified payment type is booking payment',
    function (): void {
        $paymentType = PaymentType::factory()->make([
            'id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'company_id' => 1,
            'is_available_for_refund' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkPaymentType($paymentType->id);
    }
)->throws(HttpException::class, 'Payment type cannot be booking payment.');

test(
    'checkPaymentType method throw exception when the specified payment type is loyalty point',
    function (): void {
        $paymentType = PaymentType::factory()->make([
            'id' => StaticPaymentTypes::LOYALTY_POINT->value,
            'company_id' => 1,
            'is_available_for_refund' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkPaymentType($paymentType->id);
    }
)->throws(HttpException::class, 'Payment type cannot be loyalty point.');

test(
    'checkPaymentType method throw exception when the specified payment type is gift card',
    function (): void {
        $paymentType = PaymentType::factory()->make([
            'id' => StaticPaymentTypes::GIFT_CARD->value,
            'company_id' => 1,
            'is_available_for_refund' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkPaymentType($paymentType->id);
    }
)->throws(HttpException::class, 'Payment type cannot be gift card.');

test(
    'checkPaymentType method return null when payment type is other',
    function (): void {
        $paymentType = PaymentType::factory()->make([
            'id' => 102,
            'company_id' => 1,
            'is_available_for_refund' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $response = $checkBookingPaymentRequestDetailsService->checkPaymentType($paymentType->id);
        $this->assertNull($response);
    }
);

it('checkStatusIsActive method set mismatches when booking payment status is not active', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];
    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'available_amount' => 12,
        'status' => BookingPaymentStatuses::USED->value,
    ]);

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkStatusIsActive($bookingPayment);
})->throws(HttpException::class, 'Action cannot be performed. This booking payment is not active.');

test(
    'checkProductsExist method throws an exception when some of the specified products do not match with our records.',
    function (): void {
        $requestValue = [
            [
                'product_id' => 10,
                'quantity' => 5,
            ],
        ];

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllProductsExist')
                ->once()
                ->andReturn(false);
        });

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->checkProductsExist(1, $requestValue);
    }
)->throws(HttpException::class, 'Some of the products are not available in our records.');

test(
    'checkProductsExist method returns null if products are not specified.',
    function (): void {
        $requestValue = null;

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->checkProductsExist(1, $requestValue);
        $this->assertTrue(true);
    }
);

test(
    'checksBeforeRefund method calls respective methods as expected and return proper response',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $cashier->counter_update_id,
            'available_amount' => 12,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'is_available_for_refund' => 1,
        ]);

        $bookingPaymentRefund = [
            'amount' => 12,
            'payment_type_id' => 1,
            'currency_id' => 1,
            'current_currency_rate' => 1,
            'currency_amount' => 1,
        ];

        $bookingPaymentRefundData = new BookingPaymentRefundData(...$bookingPaymentRefund);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($paymentType);
        });

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checksBeforeRefund(
            $bookingPayment,
            $bookingPaymentRefundData,
            $company
        );
    }
);

test(
    'checkMemberDetails method throws an exception when member_id & new member details specified.',
    function (): void {
        $member = [
            'id' => 1,
            'company_id' => 1,
            'created_store_id' => 1,
        ];

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->checkMemberDetails(1, $member, 1);
    }
)->throws(HttpException::class, 'Please provide either member id or member details, not both.');

test(
    'checkMemberDetails method throws an exception when member_id & new member details not specified.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->checkMemberDetails(null, null, 1);
    }
)->throws(HttpException::class, 'Member is required for the booking payment.');

test(
    'checkMemberDetails method throws an exception when first_name is not specified for new member',
    function (): void {
        $member = [
            'id' => 1,
            'company_id' => 1,
            'first_name' => null,
            'created_store_id' => 1,
        ];

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->checkMemberDetails(null, $member, 1);
    }
)->throws(HttpException::class, 'First name is required');

test(
    'checkMemberDetails method throws an exception when mobile_number is not specified for new member',
    function (): void {
        $member = [
            'id' => 1,
            'company_id' => 1,
            'mobile_number' => null,
            'first_name' => 'abcd',
            'created_store_id' => 1,
        ];

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->checkMemberDetails(null, $member, 1);
    }
)->throws(HttpException::class, 'mobile number is required');

test(
    'checkPromoters calls the doAllPromotersExist method of PromoterQueries class',
    function (): void {
        $validateData = [
            'offline_id' => 'asd1',
            'amount' => 1.00,
            'remarks' => null,
            'payment_type_id' => 1,
            'promoter_ids' => [1],
            'products' => [
                0 => [
                    'product_id' => 1,
                    'quantity' => 5,
                ],
            ],
            'member' => [],
            'happened_at' => null,
        ];

        $bookingPaymentData = new BookingPaymentData(...$validateData);

        $this->mock(PromoterQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllPromotersExist')
                ->once()
                ->andReturn(false);
        });

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();

        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $checkBookingPaymentRequestDetailsService->checkPromoters($bookingPaymentData, 1);
    }
)->throws(HttpException::class, 'Some of the promoters are not available in our records.');

it(
    'checkStoreManagerAuthorized method set mismatches when store manager id & passcode specified blank',
    function (): void {
        $validateData = [
            'offline_id' => 'asd1',
            'amount' => 1.00,
            'remarks' => null,
            'payment_type_id' => 1,
            'promoter_ids' => [1],
            'store_manager_id' => null,
            'store_manager_passcode' => null,
            'products' => [
                0 => [
                    'product_id' => 1,
                    'quantity' => 5,
                ],
            ],
            'member' => [],
            'happened_at' => null,
        ];

        $bookingPaymentData = new BookingPaymentData(...$validateData);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorized($bookingPaymentData, 1);
    }
)->throws(HttpException::class, 'Store Manager id & passcode is required to authorized booking payment');

it('checkStoreManagerAuthorized method set mismatches when store manager not found in database.', function (): void {
    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => 1,
        'store_manager_passcode' => '1234',
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => null,
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn(null);
    });

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorized($bookingPaymentData, 1);
})->throws(HttpException::class, 'Specified Store Manager does not correspond with our records.');

it('checkStoreManagerAuthorized method set mismatches when employee as store manager is inactive', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'first_name' => 'test',
        'last_name' => 'test1',
        'status' => false,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => $storeManager->passcode,
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => null,
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $mock = $this->createPartialMock(
        CheckBookingPaymentRequestDetailsService::class,
        ['checkStoreManagerAuthorizationCode']
    );

    $mock->expects($this->once())
        ->method('checkStoreManagerAuthorizationCode');

    $mock->bookingPaymentMismatches = collect([]);
    $mock->checkStoreManagerAuthorized($bookingPaymentData, 1);
})->throws(HttpException::class, 'Specified Store Manager : test test1 account is inactive. Please contact admin.');

it('checkStoreManagerAuthorized method set mismatches when store manager passcode mismatch', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => true,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => '12345',
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => null,
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $mock = $this->createPartialMock(
        CheckBookingPaymentRequestDetailsService::class,
        ['checkStoreManagerAuthorizationCode']
    );

    $mock->expects($this->once())
        ->method('checkStoreManagerAuthorizationCode');

    $mock->bookingPaymentMismatches = collect([]);
    $mock->checkStoreManagerAuthorized($bookingPaymentData, 1);
})->throws(
    HttpException::class,
    'The Store Manager provided passcode for authorization does not correspond with our records.'
);

test(
    'checkRefundPayments method throw exception when single payment credit note.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: 100,
            payment_type_id: 2,
            currency_id: 1,
            current_currency_rate: 1,
            currency_amount: 1
        );

        $checkBookingPaymentRequestDetailsService->checkRefundPayments($bookingPaymentRefundData, 1);
    }
)->throws(HttpException::class, 'Payment type cannot be credit note payment.');

test(
    'checkRefundPayments method throw exception when payment available for refund.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: 100,
            payment_type_id: 1,
            currency_id: 1,
            current_currency_rate: 1,
            currency_amount: 1
        );

        $paymentType = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'is_available_for_refund' => 0,
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($paymentType);
        });

        $checkBookingPaymentRequestDetailsService->checkRefundPayments($bookingPaymentRefundData, 1);
    }
)->throws(HttpException::class, 'Specified payment type is not available for refund.');

test(
    'checkBookingPaymentRefundAmount method throw exception when payment not available for refund.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: 100,
            payment_type_id: 1,
            currency_id: 1,
            current_currency_rate: 1,
            currency_amount: 1
        );

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'available_amount' => 12,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService->checkBookingPaymentRefundAmount(
            $bookingPayment,
            $bookingPaymentRefundData,
            $company
        );
    }
)->throws(HttpException::class, 'Specified refund amount 100 is more than available amount of the booking payment 12');

test(
    'checkBookingPaymentRefundAmount method throw exception when payment refund type full payment but pass partially payment.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: 50,
            payment_type_id: 1,
            currency_id: 1,
            current_currency_rate: 1,
            currency_amount: 1
        );

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'available_amount' => 100,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'booking_payment_refund_type' => BookingPaymentRefundTypes::FULLY->value,
            'default_country_id' => 1,
        ]);

        $checkBookingPaymentRequestDetailsService->checkBookingPaymentRefundAmount(
            $bookingPayment,
            $bookingPaymentRefundData,
            $company
        );
    }
)->throws(
    HttpException::class,
    'You cannot use booking payment partially. kindly use full booking payment. Specified payment amount is 50 and available booking payment amount is 100'
);

test(
    'checkBookingPaymentRefundAmount method return null when refund type is partially.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: 50,
            payment_type_id: 1,
            currency_id: 1,
            current_currency_rate: 1,
            currency_amount: 1
        );

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'available_amount' => 100,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'booking_payment_refund_type' => BookingPaymentRefundTypes::PARTIALLY->value,
            'default_country_id' => 1,
        ]);

        $response = $checkBookingPaymentRequestDetailsService->checkBookingPaymentRefundAmount(
            $bookingPayment,
            $bookingPaymentRefundData,
            $company
        );

        $this->assertNull($response);
    }
);

test(
    'checkBookingPaymentRefundAmount method return null when available amount and refund amount same.',
    function (): void {
        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: 100,
            payment_type_id: 1,
            currency_id: 1,
            current_currency_rate: 1,
            currency_amount: 1
        );

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'available_amount' => 100,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'booking_payment_refund_type' => BookingPaymentRefundTypes::PARTIALLY->value,
            'default_country_id' => 1,
        ]);

        $response = $checkBookingPaymentRequestDetailsService->checkBookingPaymentRefundAmount(
            $bookingPayment,
            $bookingPaymentRefundData,
            $company
        );

        $this->assertNull($response);
    }
);

test(
    'checkStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '1234',
        ]);

        $storeManager->employee = $employee;

        $validateData = [
            'offline_id' => 'asd1',
            'amount' => 1.00,
            'remarks' => null,
            'payment_type_id' => 1,
            'promoter_ids' => [1],
            'store_manager_id' => $storeManager->id,
            'store_manager_passcode' => '12345',
            'products' => [
                0 => [
                    'product_id' => 1,
                    'quantity' => 5,
                ],
            ],
            'member' => [],
            'happened_at' => null,
        ];

        $bookingPaymentData = new BookingPaymentData(...$validateData);

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $response = $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);

        $this->assertNull($response);
    }
);

test('checkStoreManagerAuthorizationCode method throw exception when code not match in database', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => true,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => '12345',
        'store_manager_authorization_code' => '12345',
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => null,
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn(null);
    });

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);
})->throws(
    HttpException::class,
    'Specified Store manager authorization code does not correspond with our records.'
);

test(
    'checkStoreManagerAuthorizationCode method throw exception when code not match with store manager',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '1234',
        ]);

        $storeManager->employee = $employee;

        $validateData = [
            'offline_id' => 'asd1',
            'amount' => 1.00,
            'remarks' => null,
            'payment_type_id' => 1,
            'promoter_ids' => [1],
            'store_manager_id' => $storeManager->id,
            'store_manager_passcode' => '12345',
            'store_manager_authorization_code' => '12345',
            'products' => [
                0 => [
                    'product_id' => 1,
                    'quantity' => 5,
                ],
            ],
            'member' => [],
            'happened_at' => null,
        ];

        $bookingPaymentData = new BookingPaymentData(...$validateData);

        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 2,
            'code' => '1234',
            'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);
    }
)->throws(HttpException::class, 'Specified Store manager authorization code and store manager not match.');

test('checkStoreManagerAuthorizationCode method throw exception when code not active', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => true,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => '12345',
        'store_manager_authorization_code' => '12345',
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => null,
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);
})->throws(HttpException::class, 'Specified Store manager authorization code is not active.');

test('checkStoreManagerAuthorizationCode method throw exception when code is expire', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => true,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => '12345',
        'store_manager_authorization_code' => '12345',
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => now()->format('Y-m-d H:i:s'),
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);
})->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test(
    'checkStoreManagerAuthorizationCode method throw exception when code is expire and happened_at set null',
    function (): void {
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'membership_id' => 1,
            'status' => true,
        ]);

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'passcode' => '1234',
        ]);

        $storeManager->employee = $employee;

        $validateData = [
            'offline_id' => 'asd1',
            'amount' => 1.00,
            'remarks' => null,
            'payment_type_id' => 1,
            'promoter_ids' => [1],
            'store_manager_id' => $storeManager->id,
            'store_manager_passcode' => '12345',
            'store_manager_authorization_code' => '12345',
            'products' => [
                0 => [
                    'product_id' => 1,
                    'quantity' => 5,
                ],
            ],
            'member' => [],
            'happened_at' => null,
        ];

        $bookingPaymentData = new BookingPaymentData(...$validateData);

        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'code' => '1234',
            'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
        $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
        $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);
    }
)->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test('checkStoreManagerAuthorizationCode return null as expected', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'membership_id' => 1,
        'status' => true,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $storeManager->employee = $employee;

    $validateData = [
        'offline_id' => 'asd1',
        'amount' => 1.00,
        'remarks' => null,
        'payment_type_id' => 1,
        'promoter_ids' => [1],
        'store_manager_id' => $storeManager->id,
        'store_manager_passcode' => '12345',
        'store_manager_authorization_code' => '12345',
        'products' => [
            0 => [
                'product_id' => 1,
                'quantity' => 5,
            ],
        ],
        'member' => [],
        'happened_at' => null,
    ];

    $bookingPaymentData = new BookingPaymentData(...$validateData);

    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $response = $checkBookingPaymentRequestDetailsService->checkStoreManagerAuthorizationCode($bookingPaymentData);
    $this->assertNull($response);
});

test('checkBoxProduct method call and return proper response', function (): void {
    $productArray = [
        'box_product_id' => 1,
        'product_id' => 1,
    ];

    $product = Product::factory()->make([
        'id' => 1,
        'name' => 'Product 1',
        'company_id' => 1,
        'unit_of_measure_id' => null,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
    ]);

    $this->mock(BoxProductQueries::class, function ($mock): void {
        $mock->shouldReceive('findBoxByIdAndProductId')
            ->once()
            ->andReturn(null);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($product): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($product);
    });
    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $this->expectException(HttpException::class);
    $this->expectExceptionMessage(
        'Product Bundle Not Found. The product bundle associated with ' . $product->name . ' was not found'
    );

    $checkBookingPaymentRequestDetailsService->checkBoxProduct([$productArray], 1);
});

test('it calls the checkPaymentCurrency method currency id is not available in company', function (): void {
    $validateData = [
        'amount' => 1.00,
        'currency_id' => 2,
        'current_currency_rate' => 1,
        'currency_amount' => 1,
        'payment_type_id' => 1,
    ];

    $bookingPaymentRefundData = new BookingPaymentRefundData(...$validateData);
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkPaymentCurrency($bookingPaymentRefundData, $this->company);
})->throws(HttpException::class, 'Payment currency id 2 is not available in this company.');

test('it calls the checkPaymentCurrency method currency rate is not available in company', function (): void {
    $validateData = [
        'amount' => 1.00,
        'currency_id' => 1,
        'current_currency_rate' => 2,
        'currency_amount' => 1,
        'payment_type_id' => 1,
    ];

    $bookingPaymentRefundData = new BookingPaymentRefundData(...$validateData);
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkPaymentCurrency($bookingPaymentRefundData, $this->company);
})->throws(
    HttpException::class,
    'Payment currency rate 2 does not match with the actual currency rate of 1 for the currency id 1'
);

test('it calls the checkPaymentCurrency method currency amount is not matching', function (): void {
    $validateData = [
        'amount' => 1.00,
        'currency_id' => 1,
        'current_currency_rate' => 1,
        'currency_amount' => 2,
        'payment_type_id' => 1,
    ];

    $bookingPaymentRefundData = new BookingPaymentRefundData(...$validateData);
    $this->company->countries = collect([$this->country]);
    foreach ($this->company->countries as $country) {
        $country->currency = $this->currency;
        $country->currency->currencyRate = $this->currencyRate;
    }

    $checkBookingPaymentRequestDetailsService = new CheckBookingPaymentRequestDetailsService();
    $checkBookingPaymentRequestDetailsService->bookingPaymentMismatches = collect([]);
    $checkBookingPaymentRequestDetailsService->checkPaymentCurrency($bookingPaymentRefundData, $this->company);
})->throws(HttpException::class, 'Payment amount 1 does not match with the actual currency amount of 2.');
