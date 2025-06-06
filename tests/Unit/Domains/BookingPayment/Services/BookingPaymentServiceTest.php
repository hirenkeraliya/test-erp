<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPayment\Services\BookingPaymentService;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\BookingPaymentPayments\Services\BookingPaymentPaymentService;
use App\Domains\BookingPaymentProduct\BookingPaymentProductQueries;
use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\BookingPayment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;

test('it correctly fetches the company and store based on cashier', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getStoreWithCompanyByCountersCounterUpdateId')
            ->once()
            ->andReturn($location);
    });

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $bookingPaymentService = new BookingPaymentService();
    $response = $bookingPaymentService->getCompanyAndStore($cashier);

    expect($response[0])->toBeInstanceOf(Location::class);
    expect($response[1])->toBe(1);
});

test('it resets booking payment products and updates promoters', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'available_amount' => 12,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $bookingPayment->mismatches = collect([]);

    $requestValue = [
        [
            'product_id' => 1,
            'quantity' => 5,
        ],
        'promoter_ids' => [1],
    ];

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $bookingPaymentProductData = new BookingPaymentProductData($requestValue, [$promoter->id]);

    $this->mock(BookingPaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('updatePromoters')
            ->once();
    });

    $this->mock(BookingPaymentProductQueries::class, function ($mock) use ($bookingPaymentProductData): void {
        $mock->shouldReceive('deleteBookingPaymentProducts')
            ->once()
            ->with(1);
        $mock->shouldReceive('createMany')
            ->once()
            ->with($bookingPaymentProductData->products, 1);
    });

    $bookingPaymentService = new BookingPaymentService();
    $bookingPaymentService->resetBookingPaymentProducts(
        $bookingPayment,
        $bookingPaymentProductData,
        $bookingPayment->mismatches
    );
});

test('it logs mismatch entries for a booking payment', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'available_amount' => 12,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $bookingPayment->mismatches = collect([]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
            ->once()
            ->andReturn($bookingPayment);
    });

    $bookingPaymentService = new BookingPaymentService();
    $bookingPaymentService->addLogMismatchEntries($bookingPayment, 'testing');
});

test('it processes a booking payment top-up and updates amount columns', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $requestValue = [
        'amount' => 12,
        'payment_type_id' => 1,
    ];

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'available_amount' => 12,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $bookingPayment->mismatches = collect([]);

    $bookingPaymentTopUpData = new BookingPaymentTopUpData(
        payment_type_id: $requestValue['payment_type_id'],
        amount: (float) $requestValue['amount'],
        remarks: '',
    );

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment, $requestValue): void {
        $mock->shouldReceive('updateAmountColumnsForTopUp')
            ->once()
            ->with($bookingPayment, $requestValue['amount']);
    });

    $bookingPaymentPaymentService = $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
        $mock->shouldReceive('savePayments')
            ->once();
    });

    $bookingPaymentService = new BookingPaymentService();
    $bookingPaymentService->bookingPaymentTopUp(
        $bookingPayment,
        $bookingPaymentTopUpData,
        $bookingPaymentPaymentService,
        $bookingPayment->mismatches,
        1
    );
});

test('it processes a booking payment refund and marks payment as refunded', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $requestValue = [
        'amount' => 12,
        'payment_type_id' => 1,
    ];

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'available_amount' => 12,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $bookingPayment->mismatches = collect([]);

    $bookingPaymentRefundData = new BookingPaymentRefundData(
        amount: (float) $requestValue['amount'],
        payment_type_id: $requestValue['payment_type_id'],
        currency_id: 1,
        current_currency_rate: 1,
        currency_amount: 1,
    );

    $this->mock(BookingPaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsRefunded')
            ->once();
    });

    $this->mock(BookingPaymentRefundQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $bookingPaymentService = new BookingPaymentService();
    $bookingPaymentService->bookingPaymentRefund($bookingPayment, $bookingPaymentRefundData, collect([]), 1);
});

test('it stores a new booking payment and handles authorization code usage', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $requestValue = [
        'amount' => 12,
        'payment_type_id' => 1,
    ];

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'available_amount' => 12,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $bookingPayment->mismatches = collect([]);

    $bookingPaymentData = new BookingPaymentData(
        offline_id: '1',
        amount: 1,
        payment_type_id: 1,
        products: [
            'product_id' => 1,
            'quantity' => 1,
            'box_product_id' => null,
            'promoter_ids' => null,
        ],
        remarks: '1',
        bill_reference_number: '1',
        happened_at: null,
        promoter_ids: null,
        member_id: 1,
        member: [],
        store_manager_id: 1,
        store_manager_passcode: '1231',
        store_manager_authorization_code: null,
        payments: [],
    );

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($bookingPayment);
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('addStoreManagerAuthorizationCodeUsage')
            ->once();
    });

    $bookingPaymentPaymentService = $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
        $mock->shouldReceive('savePayments')
            ->once();
    });

    $this->mock(BookingPaymentProductQueries::class, function ($mock): void {
        $mock->shouldReceive('createMany')
            ->once();
    });

    $bookingPaymentService = new BookingPaymentService();
    $bookingPaymentService->storeBookingPayment(
        $bookingPaymentData,
        $bookingPaymentPaymentService,
        collect([]),
        'test',
        1
    );
});
