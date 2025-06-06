<?php

declare(strict_types=1);

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\BookingPaymentProduct;
use App\Models\BookingPaymentRefund;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\PaymentType;
use App\Models\PosMismatch;
use App\Models\Promoter;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->cashier = Cashier::factory()->create();
    $this->location = Location::factory()->create([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
    ]);

    $this->bookingPayment = BookingPayment::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
        'total_amount' => 100,
        'status' => BookingPaymentStatuses::ACTIVE,
    ]);

    $this->bookingPaymentQueries = new BookingPaymentQueries();
});

test(
    'the getPaginatedBookingPaymentsWithProducts method returns the booking payments paginated list',
    function (): void {
        $this->cashier->counter_update_id = $this->counterUpdate->id;
        $this->cashier->save();

        BookingPayment::factory()->create([
            'counter_update_id' => $this->counterUpdate->id,
            'status' => BookingPaymentStatuses::USED,
        ]);

        BookingPaymentPayment::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
        ]);

        BookingPaymentPayment::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
            'counter_update_id' => $this->counterUpdate->id,
        ]);

        $filterData = [
            'member_id' => 0,
            'status' => 'active',
            'only_active' => null,
            'from_date' => Carbon::now()->format('Y-m-d'),
            'to_date' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 1,
            'search_text' => null,
            'promoter_id' => null,
            'after_updated_at' => null,
        ];

        $request = new Request($filterData);

        $request->setUserResolver(fn (): Cashier => $this->cashier);

        $response = $this->bookingPaymentQueries->getPaginatedBookingPaymentsWithProducts(
            $filterData,
            $this->companyId,
            $this->counter->location_id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
        ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount)
        ->toHaveKey('status', BookingPaymentStatuses::getValueByCaseName('active'))
        ->toHaveKeys(
            [
                'products',
                'counter_update',
                'counter_update.cashier',
                'counter_update.cashier.employee',
                'counter_update.counter',
                'member',
                'booking_payment_uses',
                'refund',
            ]
        );
    }
);

test('the markAsRefunded method updates refund amount for partially refund booking payment', function (): void {
    $this->bookingPayment->available_amount = 100;
    $this->bookingPayment->status = BookingPaymentStatuses::ACTIVE;
    $this->bookingPayment->save();

    $this->bookingPaymentQueries->markAsRefunded($this->bookingPayment, 50.0);

    $this->assertDatabaseHas('booking_payments', [
        'status' => BookingPaymentStatuses::ACTIVE->value,
        'available_amount' => 50.0,
        'id' => $this->bookingPayment->id,
    ]);
});

test('the markAsRefunded method updates the booking payment for refund', function (): void {
    $this->bookingPayment->available_amount = 100;
    $this->bookingPayment->status = BookingPaymentStatuses::ACTIVE;
    $this->bookingPayment->save();

    $this->bookingPaymentQueries->markAsRefunded($this->bookingPayment, 100.0);

    $this->assertDatabaseHas('booking_payments', [
        'status' => BookingPaymentStatuses::REFUNDED->value,
        'available_amount' => 0,
        'id' => $this->bookingPayment->id,
    ]);
});

test('the updateAmountColumnsForTopUp method updates the booking payments data', function (): void {
    $this->cashier->counter_update_id = $this->counterUpdate->id;
    $this->cashier->save();

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $this->cashier);

    $this->bookingPaymentQueries->updateAmountColumnsForTopUp($this->bookingPayment, 100);

    $this->bookingPayment->refresh();

    $this->assertDatabaseHas('booking_payments', [
        'id' => $this->bookingPayment->id,
        'total_amount' => $this->bookingPayment->total_amount,
        'available_amount' => $this->bookingPayment->available_amount,
    ]);
});

test('the getById method returns the booking payment details', function (): void {
    $response = $this->bookingPaymentQueries->getById($this->bookingPayment->id, $this->companyId, $this->location->id);

    expect($response->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount);
});

test(
    'the addNew method adds the booking payment details',
    function (): void {
        $this->cashier->counter_update_id = $this->counterUpdate->id;
        $this->cashier->save();

        $paymentType = PaymentType::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $memberId = Member::factory()->create()->id;

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $promoter = Promoter::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $storeManager = StoreManager::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $validateData = [
            'offline_id' => 'asd1',
            'amount' => 1.00,
            'remarks' => null,
            'payment_type_id' => $paymentType->id,
            'promoter_ids' => [$promoter->id],
            'store_manager_id' => $storeManager->id,
            'store_manager_passcode' => $storeManager->passcode,
            'products' => [
                0 => [
                    'product_id' => 1,
                    'quantity' => 5,
                ],
            ],
            'happened_at' => null,
            'member_id' => $memberId,
            'member' => [],
        ];

        $bookingPaymentData = new BookingPaymentData(...$validateData);
        $this->bookingPaymentQueries->addNew($bookingPaymentData, $this->cashier->counter_update_id, '000001');

        $this->assertDatabaseHas('booking_payments', [
            'offline_id' => $validateData['offline_id'],
            'counter_update_id' => $this->cashier->counter_update_id,
            'member_id' => $memberId,
            'total_amount' => $validateData['amount'],
            'available_amount' => $validateData['amount'],
            'happened_at' => $validateData['happened_at'],
            'authorizer_id' => $validateData['store_manager_id'],
            'authorizer_type' => ModelMapping::STORE_MANAGER->name,
        ]);

        $this->assertDatabaseHas('booking_payment_promoter', [
            'promoter_id' => $promoter->id,
        ]);
    }
);

test('the getByIds method returns the booking payment details', function (): void {
    $response = $this->bookingPaymentQueries->getByIds([$this->bookingPayment->id], $this->location->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount);
});

test('the markAsUsed method updates the booking payment record values as expected', function (): void {
    $this->bookingPaymentQueries->markAsUsed($this->bookingPayment, (float) $this->bookingPayment->available_amount);

    $this->assertDatabaseHas('booking_payments', [
        'id' => $this->bookingPayment->id,
        'available_amount' => 0,
        'status' => BookingPaymentStatuses::USED->value,
    ]);
});

test(
    'loadProductsMemberAndMismatchesRelations method loads the products, member, and mismatches as expected',
    function (): void {
        PosMismatch::factory()->create([
            'module_id' => $this->bookingPayment->id,
            'module_type' => $this->bookingPayment::class,
        ]);

        BookingPaymentProduct::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $promoter = Promoter::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->bookingPayment->promoters()->sync([$promoter->id]);

        $response = $this->bookingPaymentQueries->loadProductsMemberAndMismatchesRelations($this->bookingPayment);

        expect($response->toArray())
            ->toHaveKey('total_amount')
            ->toHaveKey('available_amount')
            ->toHaveKeys(
                ['products', 'member', 'mismatches', 'products.0.product', 'promoters', 'promoters.0.employee']
            );
    }
);

test(
    'loadPaymentsMemberAndMismatchesRelations method loads the booking payment payment, payment type, member, and mismatches as expected',
    function (): void {
        PosMismatch::factory()->create([
            'module_id' => $this->bookingPayment->id,
            'module_type' => $this->bookingPayment::class,
        ]);

        BookingPaymentProduct::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
        ]);

        $response = $this->bookingPaymentQueries->loadPaymentsMemberAndMismatchesRelations($this->bookingPayment);

        expect($response->toArray())
            ->toHaveKey('total_amount')
            ->toHaveKey('available_amount')
            ->toHaveKeys(['booking_payment_payments', 'member', 'mismatches']);
    }
);

test(
    'loadMemberRefundAndMismatchesRelations method loads the member, refund, refund payment type, and mismatches as expected',
    function (): void {
        PosMismatch::factory()->create([
            'module_id' => $this->bookingPayment->id,
            'module_type' => $this->bookingPayment::class,
        ]);

        BookingPaymentRefund::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
        ]);

        $response = $this->bookingPaymentQueries->loadMemberRefundAndMismatchesRelations($this->bookingPayment);

        expect($response->toArray())
            ->toHaveKey('total_amount')
            ->toHaveKey('available_amount')
            ->toHaveKeys(['member', 'refund', 'refund.payment_type', 'mismatches']);
    }
);

test('doesOfflineIdExist method returns as expected', function (): void {
    $employeeId = Employee::factory()->create([
        'company_id' => $this->companyId,
        'email' => 'employee@company.test',
    ])->id;

    $cashierGroupId = CashierGroup::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Test Cashier Group',
    ])->id;

    $cashier = Cashier::factory()->create([
        'employee_id' => $employeeId,
        'cashier_group_id' => $cashierGroupId,
        'username' => 'Cashier',
    ]);

    $location = Location::factory()->create([
        'company_id' => $this->companyId,
        'email' => 'store@company.test',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
        'name' => 'Counter 1',
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);

    $bookingPayment = BookingPayment::factory()->create([
        'offline_id' => '0000000001',
        'counter_update_id' => $counterUpdate->id,
    ]);

    $response = $this->bookingPaymentQueries->doesOfflineIdExist($bookingPayment->offline_id, $this->companyId);
    $this->assertTrue($response);

    $response = $this->bookingPaymentQueries->doesOfflineIdExist('2', $this->companyId);
    $this->assertFalse($response);
});

test(
    'the getBookingPaymentWithRelation method returns the booking payment details of given offline id or  id',
    function (): void {
        $response = $this->bookingPaymentQueries->getBookingPaymentWithRelation(
            $this->location->id,
            $this->companyId,
            $this->bookingPayment->offline_id
        );

        expect($response->toArray())
            ->toHaveKey('id', $this->bookingPayment->id)
            ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
            ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
            ->toHaveKey('available_amount', $this->bookingPayment->available_amount)
            ->toHaveKeys(
                [
                    'products',
                    'counter_update',
                    'counter_update.cashier',
                    'counter_update.cashier.employee',
                    'counter_update.counter',
                    'member',
                    'booking_payment_uses',
                    'refund',
                ]
            );
    }
);

test(
    'updatePromoters method updates the promoter ids',
    function (): void {
        $employeeOne = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $employeeTwo = Employee::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $promoterOne = Promoter::factory()->create([
            'employee_id' => $employeeOne->id,
        ]);

        $promoterTwo = Promoter::factory()->create([
            'employee_id' => $employeeTwo->id,
        ]);

        $this->bookingPayment->promoters()->sync([$promoterOne->id]);

        $this->bookingPaymentQueries->updatePromoters($this->bookingPayment, [$promoterTwo->id]);

        $this->assertDatabaseHas('booking_payment_promoter', [
            'promoter_id' => $promoterTwo->id,
        ]);

        $this->assertDatabaseMissing('booking_payment_promoter', [
            'promoter_id' => $promoterOne->id,
        ]);
    }
);

test('incrementAvailableAmountAndActivate update the available note and status', function (): void {
    $bookingPayment = BookingPayment::factory()->create([
        'available_amount' => 100,
        'status' => BookingPaymentStatuses::USED->value,
    ]);

    $this->bookingPaymentQueries->incrementAvailableAmountAndActivate($bookingPayment->id, 50);

    $this->assertDatabaseHas('booking_payments', [
        'id' => $bookingPayment->id,
        'available_amount' => 150,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);
});

test(
    'the getPaginatedBookingPaymentList method returns the booking payments paginated list',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'location_ids' => null,
            'status_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->bookingPaymentQueries->getPaginatedBookingPaymentList($filterData, $this->companyId);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
        ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount);
    }
);

test(
    'the getSumOfAvailableAmountByCompany method returns proper response',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'location_ids' => null,
            'status_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->bookingPaymentQueries->getSumOfAvailableAmountByCompany($filterData, $this->companyId);

        expect($response)->toBe($this->bookingPayment->available_amount);
    }
);

test(
    'the getBookingPaymentForExport method returns the booking payments as expected',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => null,
            'date_range' => null,
            'member_id' => null,
            'location_ids' => null,
            'status_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->bookingPaymentQueries->getBookingPaymentForExport($filterData, $this->companyId);

        expect($response->first()->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
        ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount);
    }
);

test(
    'the getPaginatedBookingPaymentListForStoreManager method returns the booking payments paginated list',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => 15,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->bookingPaymentQueries->getPaginatedBookingPaymentListForStoreManager(
            $filterData,
            $this->companyId,
            $this->location->id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
        ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount);
    }
);

test(
    'the getBookingPaymentForExportForStoreManager method returns the booking payments as expected',
    function (): void {
        $filterData = [
            'search_text' => '',
            'sort_by' => '',
            'sort_direction' => 'desc',
            'per_page' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->bookingPaymentQueries->getBookingPaymentForExportForStoreManager(
            $filterData,
            $this->companyId,
            $this->location->id
        );

        expect($response->first()->toArray())
        ->toHaveKey('id', $this->bookingPayment->id)
        ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
        ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
        ->toHaveKey('available_amount', $this->bookingPayment->available_amount);
    }
);

test(
    'the getDetailsForPrint method returns the booking payment details of given booking payment id',
    function (): void {
        $response = $this->bookingPaymentQueries->getDetailsForPrint(
            $this->bookingPayment->id,
            $this->companyId,
            $this->location->id,
        );
        expect($response->toArray())
            ->toHaveKey('id', $this->bookingPayment->id)
            ->toHaveKey('offline_id', $this->bookingPayment->offline_id)
            ->toHaveKey('total_amount', $this->bookingPayment->total_amount)
            ->toHaveKeys(['products', 'member', 'booking_payment_payments']);
    }
);

test(
    'the digitalInvoiceUpdate method update the booking payments',
    function (): void {
        $this->bookingPaymentQueries->digitalInvoiceUpdate($this->bookingPayment->id);
        $this->assertDatabaseHas('booking_payments', [
            'id' => $this->bookingPayment->id,
            'digital_invoice_submitted' => true,
        ]);
    }
);

test(
    'the getBookingPaymentByStoreIdCounterId method update the booking payments',
    function (): void {
        $response = $this->bookingPaymentQueries->getBookingPaymentByStoreIdCounterId(
            $this->bookingPayment->offline_id,
            $this->location->id,
            $this->counter->id
        );
        expect($response)
         ->toHaveKey('id', $this->bookingPayment->id)
         ->toHaveKey('digital_invoice_submitted', $this->bookingPayment->digital_invoice_submitted);
    }
);

test(
    'the updateMember method update the booking payments member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $this->assertDatabaseHas(BookingPayment::class, [
            'id' => $this->bookingPayment->getKey(),
            'member_id' => $this->bookingPayment->member_id,
        ]);

        $this->bookingPaymentQueries->updateMember($this->bookingPayment->member_id, $member->getKey());

        $this->assertDatabaseHas(BookingPayment::class, [
            'id' => $this->bookingPayment->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);
