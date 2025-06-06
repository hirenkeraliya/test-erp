<?php

declare(strict_types=1);

use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentPayments\Services\BookingPaymentPaymentService;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Domains\GiftCard\GiftCardQueries;
use App\Domains\GiftCardTransaction\GiftCardTransactionQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\CreditNote;
use App\Models\GiftCard;
use App\Models\Location;
use App\Models\PaymentType;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->bookingPaymentPaymentService = new BookingPaymentPaymentService();
    $this->companyId = 1;

    $this->saleDetails = [
        'offline_id' => 'abcd',
        'amount' => 12,
        'remarks' => null,
        'payment_type_id' => 1,
        'member' => [
            'type_id' => 1,
            'first_name' => 'first_name',
            'mobile_number' => '222222222',
        ],
    ];

    $this->paymentData = new BookingPaymentData(
        offline_id: $this->saleDetails['offline_id'],
        amount: (float) $this->saleDetails['amount'],
        payment_type_id: $this->saleDetails['payment_type_id'],
        products: null,
        remarks: null,
        bill_reference_number: null,
        happened_at: null,
        promoter_ids: null,
        member_id: 1,
        member: null,
        store_manager_id: 1,
        store_manager_passcode: '123',
        payments: [
            [
                'payment_type_id' => 1,
                'amount' => '100',
            ],
        ],
    );

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->bookingPaymentPaymentService->paymentMismatches = collect([]);

    $this->bookingPaymentPaymentService->paymentData = $this->paymentData;
});

test('setDetails method works as expected', function (): void {
    $this->bookingPaymentPaymentService->setDetails($this->paymentData, $this->location, 1, 1);

    $this->assertTrue($this->bookingPaymentPaymentService->paymentMismatches->toArray() === []);
});

test('getPaymentAmount method returns as expected', function (): void {
    $response = $this->bookingPaymentPaymentService->getPaymentAmount();
    $this->assertEquals($response, 100);
});

test(
    'checkPaymentTypes method throws an exception when single and multiple payment pass',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Simultaneous single and multiple payments are not allowed.');

test(
    'checkPaymentTypes method sets saleMismatches when booking payment amount not match',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'The payment amount and booking payment amount do not match.');

test(
    'checkPaymentTypes method sets saleMismatches when Some of the payment types are inactive',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $paymentTypes = new Collection([
            PaymentType::factory()->make([
                'id' => 1,
                'company_id' => $this->companyId,
                'name' => 'Payment 1',
                'is_member_required' => false,
                'status' => false,
            ]),
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Some of the payment types are inactive.');

test(
    'checkPaymentTypes method throws an exception when payment is not available',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $paymentTypes = new Collection([]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Some of the payment types are not available in our records.');

test(
    'checkPaymentTypes method throws an exception when payment type is booking payment',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;
        $this->bookingPaymentPaymentService->paymentData->payments = [
            [
                'payment_type_id' => 3,
                'amount' => '100',
            ],
        ];

        $paymentTypes = new Collection([
            PaymentType::factory()->make([
                'id' => 1,
                'company_id' => $this->companyId,
                'name' => 'Payment 1',
                'is_member_required' => true,
                'status' => true,
            ]),
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Payment type cannot be booking payment.');

test(
    'checkPaymentTypes method throws an exception when payment type is loyalty point',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;
        $this->bookingPaymentPaymentService->paymentData->payments = [
            [
                'payment_type_id' => 4,
                'amount' => '100',
            ],
        ];

        $paymentTypes = new Collection([
            PaymentType::factory()->make([
                'id' => 1,
                'company_id' => $this->companyId,
                'name' => 'Payment 1',
                'is_member_required' => true,
                'status' => true,
            ]),
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Payment type cannot be loyalty point.');

test(
    'checkPaymentTypes method throws an exception when payment type is gift card',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;
        $this->bookingPaymentPaymentService->paymentData->payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
            ],
        ];

        $paymentTypes = new Collection([
            PaymentType::factory()->make([
                'id' => 1,
                'company_id' => $this->companyId,
                'name' => 'Payment 1',
                'is_member_required' => true,
                'status' => true,
            ]),
        ]);

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentTypes): void {
            $mock->shouldReceive('getByIds')
                ->once()
                ->andReturn($paymentTypes);
        });

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'Gift Card id must be provided when payment type is gift card.');

test(
    'checkPaymentType method throws an exception when payment type is booking payment',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = 3;

        $this->bookingPaymentPaymentService->checkPaymentType();
    }
)->throws(HttpException::class, 'Payment type cannot be booking payment.');

test(
    'checkPaymentType method throws an exception when payment type is gift card',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = 5;

        $this->bookingPaymentPaymentService->checkPaymentType();
    }
)->throws(HttpException::class, 'Payment type cannot be gift card.');

test(
    'savePayments method call addNew method of BookingPaymentPaymentQueries class',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = 1;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;
        $this->bookingPaymentPaymentService->paymentData->payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
            ],
        ];

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $bookingPaymentPayment = BookingPaymentPayment::factory()->make([
            'id' => 1,
            'booking_payment_id' => 1,
            'counter_update_id' => 1,
            'payment_type_id' => 1,
        ]);

        $this->mock(BookingPaymentPaymentQueries::class, function ($mock) use ($bookingPaymentPayment): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($bookingPaymentPayment);
        });

        $this->bookingPaymentPaymentService->savePayments($bookingPayment, 1);
    }
);

test(
    'savePayments method call addNewForMultiple method of BookingPaymentPaymentQueries class',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;
        $this->bookingPaymentPaymentService->paymentData->payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
            ],
        ];

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $this->mock(BookingPaymentPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewForMultiple')
                ->once();
        });

        $this->bookingPaymentPaymentService->savePayments($bookingPayment, 1);
    }
);

test(
    'checkPaymentTypes method throws an exception when payment not pass',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;
        $this->bookingPaymentPaymentService->paymentData->payments = [];

        $this->bookingPaymentPaymentService->checkPaymentTypes();
    }
)->throws(HttpException::class, 'payment is required for booking payment.');

test(
    'checkPaymentTypes method throws an exception when gift_card_id key not pass',
    function (): void {
        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
            ],
        ];

        $this->bookingPaymentPaymentService->validateGiftCards($payments);
    }
)->throws(HttpException::class, 'Gift Card id must be provided when payment type is gift card.');

test(
    'checkPaymentTypes method throws an exception when when payment type not gift cart and gift_card_id not pass',
    function (): void {
        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => null,
            ],
        ];

        $this->bookingPaymentPaymentService->validateGiftCards($payments);
    }
)->throws(HttpException::class, 'Gift Card id must be provided when payment type is gift card.');

test(
    'checkPaymentTypes method return null when payment type not gift cart',
    function (): void {
        $payments = [
            [
                'payment_type_id' => 4,
                'amount' => '100',
                'gift_card_id' => null,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
);

test(
    'checkPaymentTypes method throws an exception when gift cards are not available',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Some of the gift cards are not available in our records.');

test(
    'checkPaymentTypes method throws an exception when expiry_date not match',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Expired gift card (number - [123]) was used for making a payment.');

test(
    'checkPaymentTypes method throws an exception when gift cart SINGLE_USE_ONLY and already used',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::USED->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Specified Gift card (number - [123]) is single use only.');

test(
    'checkPaymentTypes method throws an exception when gift cart not active',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::EXPIRED->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Gift card (number - [123]) is not active.');

test(
    'checkPaymentTypes method throws an exception when gift cart amount not match',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
            'available_amount' => 50,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
)->throws(
    HttpException::class,
    'The requested payment amount of 100 exceeds the available amount of the gift card (number - [123]) , which is 50.'
);

test(
    'checkPaymentTypes method throws an exception when company is deferent',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 2,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
            'available_amount' => 150,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'You cannot use different companies gift card.');

test(
    'checkPaymentTypes method return null when all check pass',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
            'available_amount' => 150,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            [
                'payment_type_id' => 5,
                'amount' => '100',
                'gift_card_id' => 1,
            ],
        ];

        $response = $this->bookingPaymentPaymentService->validateGiftCards($payments);
        $this->assertNull($response);
    }
);

test(
    'useGiftCardIfApplicable method return null when gift_card_id not set',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
            'available_amount' => 150,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            'payment_type_id' => 5,
            'amount' => '100',
        ];

        $response = $this->bookingPaymentPaymentService->useGiftCardIfApplicable($payments, 1);
        $this->assertNull($response);
    }
);

test(
    'useGiftCardIfApplicable method return null when gift_card_id set null',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
            'available_amount' => 150,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $payments = [
            'payment_type_id' => 5,
            'amount' => '100',
            'gift_card_id' => null,
        ];

        $response = $this->bookingPaymentPaymentService->useGiftCardIfApplicable($payments, 1);
        $this->assertNull($response);
    }
);

test(
    'useGiftCardIfApplicable method call addNew method of giftCardTransactionQueries class',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;
        $this->bookingPaymentPaymentService->paymentData->payment_type_id = null;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $giftCard = GiftCard::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'number' => 123,
            'status' => GiftCardStatuses::ACTIVE->value,
            'type_id' => GiftCardTypes::SINGLE_USE_ONLY->value,
            'expiry_date' => now()->format('Y-m-d'),
            'available_amount' => 150,
        ]);

        $this->bookingPaymentPaymentService->giftCards = collect([$giftCard]);

        $this->mock(GiftCardQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(GiftCardTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $payments = [
            'payment_type_id' => 5,
            'amount' => '100',
            'gift_card_id' => 1,
        ];

        $response = $this->bookingPaymentPaymentService->useGiftCardIfApplicable($payments, 1);
        $this->assertNull($response);
    }
);

test(
    'validateCreditNotes method throws exception when specified payment type id is not credit note',
    function (): void {
        $this->paymentData->payments[] = [
            'payment_type_id' => 2,
            'amount' => '100',
        ];

        $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
    }
)->throws(HttpException::class, 'Credit note id must be provided when payment type is credit note.');

test('validateCreditNotes method throws exception when creditNotes is empty', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::USED->value,
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 2,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([]);
    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
})->throws(HttpException::class, 'Some of the credit notes are not available in our records.');

test('validateCreditNotes method throws exception when creditNote expire', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::USED->value,
        'expiry_date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 2,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);
    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
})->throws(HttpException::class, 'Credit note is expired. You are not able to use expired credit notes.');

test('validateCreditNotes method throws exception when Credit note is not active', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::USED->value,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 2,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);
    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
})->throws(HttpException::class, 'Credit note is not active.');

test('validateCreditNotes method throws exception when member is not same credit note member', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::ACTIVE->value,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 1,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);
    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 2);
})->throws(HttpException::class, 'Selected member is not same as the credit note member');

test('validateCreditNotes method throws exception when payment type not credit note', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::ACTIVE->value,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 1,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);
    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
})->throws(HttpException::class, 'The Payment Type must be a credit note when you provide the credit note id.');

test('validateCreditNotes method throws exception when available_amount less than amount', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::ACTIVE->value,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'available_amount' => 50,
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 2,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);

    $this->expectException(HttpException::class);
    $this->expectExceptionMessage(
        'Specified payment amount exceeds the credit note available amount ' . $creditNote->available_amount . ' Requested Payment Amount is 100'
    );

    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
});

test('validateCreditNotes method throws exception when used different company', function (): void {
    $creditNote = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
        'status' => CreditNoteStatuses::ACTIVE->value,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'available_amount' => 500,
    ]);

    $this->paymentData->payments[] = [
        'payment_type_id' => 2,
        'amount' => '100',
        'credit_note_id' => $creditNote->id,
    ];
    $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdByCounterUpdateId')
            ->once()
            ->andReturn(2);
    });

    $this->bookingPaymentPaymentService->validateCreditNotes($this->paymentData, 1, 1);
})->throws(HttpException::class, 'You cannot use different companies credit notes.');

test(
    'saveCreditNotes method return null when payments null',
    function (): void {
        $this->bookingPaymentPaymentService->paymentData->payments = null;

        $response = $this->bookingPaymentPaymentService->saveCreditNotes(1, 1);
        $this->assertNull($response);
    }
);

test(
    'saveCreditNotes method return null when credit_note_id not exists in payments array',
    function (): void {
        $response = $this->bookingPaymentPaymentService->saveCreditNotes(1, 1);
        $this->assertNull($response);
    }
);

test(
    'saveCreditNotes method return null when creditNote empty multiple payment',
    function (): void {
        $this->bookingPaymentPaymentService->paymentData->payments[0]['credit_note_id'] = 1;
        $this->bookingPaymentPaymentService->creditNotes = collect([]);

        $response = $this->bookingPaymentPaymentService->saveCreditNotes(1, 1);
        $this->assertNull($response);
    }
);

test(
    'saveCreditNotes method call addNew method of CreditNoteQueries and CreditNoteUseQueries class multiple payment',
    function (): void {
        $creditNote = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'expiry_date' => now()->addDay()->format('Y-m-d'),
            'available_amount' => 500,
        ]);
        $this->bookingPaymentPaymentService->paymentData->payments[0]['credit_note_id'] = 1;
        $this->bookingPaymentPaymentService->paymentData->payments[0]['amount'] = 1;
        $this->bookingPaymentPaymentService->creditNotes = collect([$creditNote]);

        $this->mock(CreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('decreaseAvailableAmountAndMarkAsUsed')
                ->once();
        });

        $this->mock(CreditNoteUseQueries::class, function ($mock): void {
            $mock->shouldReceive('recordBookingPaymentUse')
                ->once();
        });

        $response = $this->bookingPaymentPaymentService->saveCreditNotes(1, 1);
        $this->assertNull($response);
    }
);

test(
    'checkPaymentType method throws an exception when payment type is credit note',
    function (): void {
        $this->bookingPaymentPaymentService->companyId = 1;

        $this->bookingPaymentPaymentService->paymentData->payment_type_id = 2;
        $this->bookingPaymentPaymentService->paymentData->amount = 100;

        $this->bookingPaymentPaymentService->checkPaymentType();
    }
)->throws(HttpException::class, 'Payment type cannot be credit note payment.');
