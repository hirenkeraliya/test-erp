<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\DataObjects\PaginatedBookingPaymentsDataForPos;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPayment\Services\BookingPaymentService;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\BookingPaymentPayments\Services\BookingPaymentPaymentService;
use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\BookingPaymentRefund\Services\CheckBookingPaymentRequestDetailsService;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sequence\SequenceQueries;
use App\Http\Controllers\Api\Pos\BookingPaymentController;
use App\Models\BookingPayment;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\PaymentType;
use App\Models\Promoter;
use App\Models\Sequence;
use Illuminate\Auth\Access\Gate as AccessGate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $gate = $this->mock(AccessGate::class, function ($mock): void {
        $mock->shouldReceive('allows')
             ->andReturn(true);
    });

    Gate::swap($gate);
});

test(
    'it calls getPaginatedBookingPaymentsWithProducts method of the BookingPaymentQueries class and returns the paginated booking payments list',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request();

        $paginatedBookingPaymentsData = [
            'member_id' => 1,
            'promoter_id' => 1,
            'status' => BookingPaymentStatuses::ACTIVE->name,
            'from_date' => '',
            'to_date' => '',
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'search_text' => '',
            'after_updated_at' => null,
        ];
        $paginatedBookingPaymentsDataForPos = new PaginatedBookingPaymentsDataForPos(...$paginatedBookingPaymentsData);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getStoreWithCompanyByCountersCounterUpdateId')
                ->once()
                ->with($cashier->counter_update_id)
                ->andReturn($location);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedBookingPaymentsWithProducts')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->getPaginatedBookingPayments(
            $request,
            $paginatedBookingPaymentsDataForPos
        );

        expect($response['booking_payments']->resource);
    }
);

test('getBookingPaymentStatuses method returns the list of booking payment statuses', function (): void {
    $bookingPaymentController = new BookingPaymentController();
    $response = $bookingPaymentController->getBookingPaymentStatuses();
    expect($response)->toHaveKey('booking_payment_statuses');
});

test(
    'it calls updateAmountColumnsForTopUp method of BookingPaymentPaymentQueries class and add new booking payment payment.',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];
        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $cashier->counter_update_id,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $requestValue = [
            'amount' => 12,
            'payment_type_id' => 1,
        ];

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $bookingPayment->mismatches = collect([]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getStoreWithCompanyByCountersCounterUpdateId')
                ->times(1)
                ->andReturn($location);
        });

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $requestValue): void {
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->andReturn($requestValue);
            $mock->shouldReceive('route');
        });

        $bookingPaymentTopUpData = new BookingPaymentTopUpData(
            payment_type_id: (int) $request->payment_type_id,
            amount: (float) $request->amount,
            remarks: '',
        );

        $this->mock(CheckBookingPaymentRequestDetailsService::class, function ($mock): void {
            $mock->shouldReceive('prepareAndCheckPaymentStatus')
                 ->once();
            $mock->bookingPaymentMismatches = collect([]);
        });

        $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
            $mock->paymentMismatches = collect([]);
            $mock->shouldReceive('prepareAndCheckPayment')
                ->once();
            $mock->shouldReceive('savePayments')
                ->once();
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($requestValue, $bookingPayment): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('updateAmountColumnsForTopUp')
                ->once()
                ->with($bookingPayment, $requestValue['amount']);
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                ->once()
                ->andReturn($bookingPayment);
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->bookingPaymentTopUp(
            $request,
            $bookingPaymentTopUpData,
            $bookingPayment->id
        );

        expect($response['booking_payment_top_up']->resource->toArray())
            ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
    }
);

test(
    'resetBookingPaymentProducts method calls the respective queries class and same class methods as expected.',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $requestValue = [
            [
                'product_id' => 1,
                'quantity' => 5,
            ],
            'promoter_ids' => [1],
        ];

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $cashier->counter_update_id,
            'available_amount' => 12,
            'status' => BookingPaymentStatuses::ACTIVE->value,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
        ]);

        $bookingPayment->mismatches = collect([]);

        $this->createPartialMock(CheckBookingPaymentRequestDetailsService::class, [
            'checkProductsExist', 'checkPromoters',
        ]);

        $request = $this->mock(Request::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('route');
        });

        $bookingPaymentProductData = new BookingPaymentProductData($requestValue, [$promoter->id]);

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllProductsExist')
                ->once()
                ->andReturn(true);
        });

        $this->mock(PromoterQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllPromotersExist')
                ->once()
                ->andReturn(true);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn(new BookingPayment([
                    'counter_update_id' => 1,
                    'status' => 1,
                ]));
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                ->once()
                ->andReturn($bookingPayment);
        });

        $this->mock(BookingPaymentService::class, function ($mock) use ($location, $bookingPayment): void {
            $mock->shouldReceive('resetBookingPaymentProducts')
                ->once();
            $mock->shouldReceive('addLogMismatchEntries')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('getCompanyAndStore')
                ->once()
                ->andReturn([$location, 1]);
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->resetBookingPaymentProducts($request, $bookingPaymentProductData, 1);

        expect($response['booking_payment_products']->resource->toArray())
            ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
    }
);

test(
    'it calls the addNew method of the booking payment refund queries class and markAsRefunded method of the bookingPaymentQueries class',
    function (): void {
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

        $requestValue = [
            'amount' => 12,
            'payment_type_id' => $paymentType->id,
        ];

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $bookingPayment->mismatches = collect([]);

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $requestValue): void {
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->andReturn($requestValue);
            $mock->shouldReceive('route');
        });

        $bookingPaymentRefundData = new BookingPaymentRefundData(
            amount: (float) $request->amount,
            payment_type_id: (int) $request->payment_type_id,
            currency_id: null,
            current_currency_rate: null,
            currency_amount: null,
        );

        $this->mock(CheckBookingPaymentRequestDetailsService::class, function ($mock): void {
            $mock->shouldReceive('prepareAndAuthorizeRefund')
                 ->once();
            $mock->bookingPaymentMismatches = collect([]);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getStoreWithCompanyByCountersCounterUpdateId')
                ->times(1)
                ->andReturn($location);
        });

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(BookingPaymentRefundQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('markAsRefunded')
                ->once();
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                ->andReturn($bookingPayment);
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->bookingPaymentRefund(
            $request,
            $bookingPaymentRefundData,
            $bookingPayment->id
        );

        expect($response['booking_payment_refund']->resource->toArray())
            ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
    }
);

test(
    'It throws an exception when The counter has not been opened yet But, try to add new booking payment',
    function (): void {
        $cashier = makeCashierForPosWithoutCounterUpdateId();
        $bookingPaymentData = new BookingPaymentData(
            offline_id: '1',
            amount: 1,
            payment_type_id: 1,
            products: [],
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
        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $bookingPaymentController = new BookingPaymentController();
        $bookingPaymentController->store($bookingPaymentData, $request);
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');

test('it calls addNew method of BookingPaymentQueries class', function (): void {
    $cashier = makeCashierForPosWithCounterUpdateId();

    $requestValue = [
        'offline_id' => 'abcd',
        'member_id' => 1,
        'amount' => 12,
        'remarks' => null,
        'payment_type_id' => 1,
        'products' => [
            [
                'product_id' => 100,
                'quantity' => 4,
            ],
        ],
    ];

    $bookingPayment = BookingPayment::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $bookingPayment->mismatches = collect([]);

    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->company = $company;

    $bookingPaymentData = new BookingPaymentData(
        offline_id: '1',
        amount: 1,
        payment_type_id: 1,
        products: [],
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

    $request = $this->mock(Request::class, function ($mock) use ($cashier, $requestValue): void {
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('all')
            ->andReturn($requestValue);
        $mock->shouldReceive('route');
    });

    $this->mock(BookingPaymentService::class, function ($mock) use ($location, $bookingPayment): void {
        $mock->shouldReceive('getCompanyAndStore')
            ->once()
            ->andReturn([$location, 1]);
        $mock->shouldReceive('storeBookingPayment')
            ->once()
            ->andReturn($bookingPayment);
        $mock->shouldReceive('addLogMismatchEntries')
            ->once()
            ->andReturn($bookingPayment);
    });

    $this->mock(CheckBookingPaymentRequestDetailsService::class, function ($mock): void {
        $mock->shouldReceive('validateBookingPaymentRequestAndMember')
             ->once();
        $mock->bookingPaymentMismatches = collect([]);
    });

    $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
        $mock->paymentMismatches = collect([]);
        $mock->shouldReceive('prepareAndCheckPayment')
            ->once();
    });

    $sequence = Sequence::factory()->make([
        'number' => 0o000001,
        'location_id' => 1,
    ]);

    $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($sequence);
    });

    $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
        $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                ->andReturn($bookingPayment);
    });

    $bookingPaymentController = new BookingPaymentController();
    $response = $bookingPaymentController->store($bookingPaymentData, $request);

    expect($response['booking_payment_store']->resource->toArray())
        ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
});

test(
    'it will not call addNew method of BookingPaymentProductQueries class when the products are not specified.',
    function (): void {
        $cashier = makeCashierForPosWithCounterUpdateId();

        $requestValue = [
            'offline_id' => 'abcd',
            'member_id' => 1,
            'amount' => 12,
            'remarks' => null,
            'payment_type_id' => 1,
        ];

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $bookingPayment->mismatches = collect([]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $company;

        $bookingPaymentData = new BookingPaymentData(
            offline_id: '1',
            amount: 1,
            payment_type_id: 1,
            products: [],
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

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $requestValue): void {
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->andReturn($requestValue);
            $mock->shouldReceive('route');
        });

        $this->mock(BookingPaymentService::class, function ($mock) use ($location, $bookingPayment): void {
            $mock->shouldReceive('getCompanyAndStore')
                ->once()
                ->andReturn([$location, 1]);
            $mock->shouldReceive('storeBookingPayment')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('addLogMismatchEntries')
                ->once()
                ->andReturn($bookingPayment);
        });

        $this->mock(CheckBookingPaymentRequestDetailsService::class, function ($mock): void {
            $mock->shouldReceive('validateBookingPaymentRequestAndMember')
                ->once();
            $mock->bookingPaymentMismatches = collect([]);
        });

        $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
            $mock->paymentMismatches = collect([]);
            $mock->shouldReceive('prepareAndCheckPayment')
                ->once();
        });

        $sequence = Sequence::factory()->make([
            'number' => 0o000001,
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                        ->andReturn($bookingPayment);
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->store($bookingPaymentData, $request);

        expect($response['booking_payment_store']->resource->toArray())
            ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
    }
);

test(
    'it will call getMemberByMobileNumber method of MemberQueries class when the existing member found',
    function (): void {
        $cashier = makeCashierForPosWithCounterUpdateId();

        $requestValue = [
            'offline_id' => 'abcd',
            'amount' => 12,
            'remarks' => null,
            'payment_type_id' => 1,
            'member' => [
                'first_name' => 'first_name',
                'mobile_number' => '111111111',
            ],
        ];

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $bookingPayment->mismatches = collect([]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $company;

        $bookingPaymentData = new BookingPaymentData(
            offline_id: '1',
            amount: 1,
            payment_type_id: 1,
            products: [],
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

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $requestValue): void {
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->andReturn($requestValue);
            $mock->shouldReceive('route');
        });

        $this->mock(CheckBookingPaymentRequestDetailsService::class, function ($mock): void {
            $mock->shouldReceive('validateBookingPaymentRequestAndMember')
                ->once();
            $mock->bookingPaymentMismatches = collect([]);
        });

        $this->mock(BookingPaymentService::class, function ($mock) use ($location, $bookingPayment): void {
            $mock->shouldReceive('getCompanyAndStore')
                ->once()
                ->andReturn([$location, 1]);
            $mock->shouldReceive('storeBookingPayment')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('addLogMismatchEntries')
                ->once()
                ->andReturn($bookingPayment);
        });

        $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
            $mock->paymentMismatches = collect([]);
            $mock->shouldReceive('prepareAndCheckPayment')
                ->once();
        });

        $sequence = Sequence::factory()->make([
            'number' => 0o000001,
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                        ->andReturn($bookingPayment);
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->store($bookingPaymentData, $request);

        expect($response['booking_payment_store']->resource->toArray())
            ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
    }
);

test(
    'it will call addNew method of MemberQueries class when the existing member not found to add new member',
    function (): void {
        $cashier = makeCashierForPosWithCounterUpdateId();

        $requestValue = [
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

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $location->company = $company;

        $bookingPayment = BookingPayment::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $bookingPayment->mismatches = collect([]);

        $bookingPaymentData = new BookingPaymentData(
            offline_id: '1',
            amount: 1,
            payment_type_id: 1,
            products: [],
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

        $request = $this->mock(Request::class, function ($mock) use ($cashier, $requestValue): void {
            $mock->shouldReceive('user')
                ->once()
                ->andReturn($cashier);
            $mock->shouldReceive('all')
                ->andReturn($requestValue);
            $mock->shouldReceive('route');
        });

        $this->mock(BookingPaymentService::class, function ($mock) use ($location, $bookingPayment): void {
            $mock->shouldReceive('getCompanyAndStore')
                ->once()
                ->andReturn([$location, 1]);
            $mock->shouldReceive('storeBookingPayment')
                ->once()
                ->andReturn($bookingPayment);
            $mock->shouldReceive('addLogMismatchEntries')
                ->once()
                ->andReturn($bookingPayment);
        });

        $this->mock(CheckBookingPaymentRequestDetailsService::class, function ($mock): void {
            $mock->shouldReceive('validateBookingPaymentRequestAndMember')
                ->once();
            $mock->bookingPaymentMismatches = collect([]);
        });

        $this->mock(BookingPaymentPaymentService::class, function ($mock): void {
            $mock->paymentMismatches = collect([]);
            $mock->shouldReceive('prepareAndCheckPayment')
                ->once();
        });

        $sequence = Sequence::factory()->make([
            'number' => 0o000001,
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock) use ($bookingPayment): void {
            $mock->shouldReceive('loadProductsMemberAndMismatchesRelations')
                        ->andReturn($bookingPayment);
        });

        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->store($bookingPaymentData, $request);

        expect($response['booking_payment_store']->resource->toArray())
            ->toHaveKeys(['id', 'member_id', 'total_amount', 'available_amount', 'status']);
    }
);

test(
    'it calls the getBookingPaymentDetails method and returns the booking payment details of given offline id',
    function (): void {
        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request([
            'employee_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
            $mock->shouldReceive('getStoreWithCompanyByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
        });

        $this->mock(BookingPaymentQueries::class, function ($mock): void {
            $mock->shouldReceive('getBookingPaymentWithRelation')
            ->once()
            ->andReturn(new BookingPayment([]));
        });

        $bookingPaymentController = new BookingPaymentController();
        $bookingPaymentController->getBookingPaymentDetails($request, '12345');
    }
);

test(
    'getSequenceNumber method call and return the sequence number',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'country_id' => 1,
        ]);

        $sequence = Sequence::factory()->make([
            'number' => '000001',
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });
        $bookingPaymentController = new BookingPaymentController();
        $response = $bookingPaymentController->getSequenceNumber($location);
        expect($response)->toBeString();
    }
);
