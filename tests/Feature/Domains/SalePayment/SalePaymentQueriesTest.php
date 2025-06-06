<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Carbon\Carbon;

test(
    'the getByCounterUpdateIdWithRelations method returns the sale payments by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();
        $sale = Sale::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => SaleStatus::REGULAR_SALE->value,
        ]);
        $salePayment = SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => null,
        ]);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $response = $salePaymentQueries->getByCounterUpdateIdWithRelations($counterUpdate->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $salePayment->id)
            ->toHaveKey('payment_type_id', $salePayment->payment_type_id)
            ->toHaveKey('amount', $salePayment->amount)
            ->toHaveKeys(['payment_type']);
    }
);

test('new sale payment can be added', function (): void {
    $sale = Sale::factory()->create();
    $paymentType = PaymentType::factory()->create();

    $paymentDetails = [
        'type_id' => $paymentType->id,
        'amount' => 555,
    ];

    $salePaymentQueries = new SalePaymentQueries();
    $happenedAt = now()->format('Y-m-d H:i:s');
    $response = $salePaymentQueries->addNew($sale, $happenedAt, $paymentDetails);

    expect($response)->toBeInt();

    $this->assertDatabaseHas('sale_payments', [
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentDetails['type_id'],
        'counter_update_id' => null,
        'amount' => $paymentDetails['amount'],
        'happened_at' => $happenedAt,
    ]);
});

test('addNew method add the counter_update_id if sale is layaway', function (): void {
    $sale = Sale::factory()->create();
    $paymentType = PaymentType::factory()->create();

    $paymentDetails = [
        'type_id' => $paymentType->id,
        'amount' => 555,
    ];

    $salePaymentQueries = new SalePaymentQueries();
    $happenedAt = now()->format('Y-m-d H:i:s');
    $salePaymentQueries->addNew($sale, $happenedAt, $paymentDetails);

    $this->assertDatabaseHas('sale_payments', [
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentDetails['type_id'],
        'counter_update_id' => null,
        'amount' => (string) $paymentDetails['amount'],
        'happened_at' => $happenedAt,
    ]);
});

test('the getByStoreIdForSalesCollectionExport method returns the sale payments by filters', function (): void {
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
    ]);
    $cashierGroup = CashierGroup::factory()->create([
        'company_id' => $company->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
        'cashier_group_id' => $cashierGroup->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => Carbon::now(),
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => null,
        'is_exchange' => false,
    ]);
    $payment = PaymentType::factory()->create([
        'company_id' => $company->id,
    ]);
    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'payment_type_id' => $payment->id,
        'counter_update_id' => null,
        'happened_at' => Carbon::now(),
    ]);
    $filterData = [
        'location_ids' => [$location->id],
        'counter_ids' => null,
        'cashier_ids' => null,
        'date_range' => [Carbon::now()->yesterday()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
    ];
    $salePaymentQueries = resolve(SalePaymentQueries::class);
    $response = $salePaymentQueries->getByStoreIdForSalesCollectionExport($filterData);
    expect($response->first()->toArray())
        ->toHaveKeys(['date', 'amount', 'payment_name']);
});

test(
    'the getSalePaymentIdAndAmountOfBookingPayment method returns the sale payment by sale id',
    function (): void {
        $company = Company::factory()->create();
        $sale = Sale::factory()->create();
        PaymentType::factory()->create([
            'id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
            'company_id' => $company->id,
            'name' => StaticPaymentTypes::BOOKING_PAYMENT->name,
        ]);
        $salePayment = SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'counter_update_id' => null,
            'payment_type_id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
        ]);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $response = $salePaymentQueries->getSalePaymentIdAndAmountOfBookingPayment($sale->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $salePayment->id)
            ->toHaveKey('amount', $salePayment->amount);
    }
);
test(
    'the getSalePaymentIdAndAmountOfCreditNote method returns the sale payment by sale id',
    function (): void {
        $company = Company::factory()->create();
        PaymentType::factory()->create([
            'id' => StaticPaymentTypes::CREDIT_NOTE->value,
            'company_id' => $company->id,
            'name' => StaticPaymentTypes::CREDIT_NOTE->name,
        ]);
        $sale = Sale::factory()->create();
        $salePayment = SalePayment::factory()->create([
            'sale_id' => $sale->id,
            'payment_type_id' => StaticPaymentTypes::CREDIT_NOTE->value,
        ]);
        $salePaymentQueries = resolve(SalePaymentQueries::class);
        $response = $salePaymentQueries->getSalePaymentIdAndAmountOfCreditNote($sale->id);
        expect($response->first()->toArray())
            ->toHaveKey('id', $salePayment->id)
            ->toHaveKey('amount', $salePayment->amount);
    }
);

test('getPaymentTypeListForReport method returns payment type list as expected.', function (): void {
    $companyId = Company::factory()->create()->id;
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
        'company_id' => $companyId,
        'name' => 'ABCD',
        'code' => 'XYZW',
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $designation = Designation::factory()->create([
        'company_id' => $companyId,
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $companyId,
        'designation_id' => $designation->id,
    ]);
    $cashier = Cashier::factory()->create([
        'employee_id' => $employee->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
        'cashier_id' => $cashier->id,
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => now()->format('Y-m-d'),
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => null,
        'is_exchange' => false,
    ]);
    $paymentType = PaymentType::factory()->create([
        'company_id' => $companyId,
    ]);
    $salePayment = SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentType->id,
        'counter_update_id' => $counterUpdate->id,
        'amount' => 10,
    ]);
    $salePaymentQueries = resolve(SalePaymentQueries::class);
    $response = $salePaymentQueries->getPaymentTypeListForReport([
        'location_ids' => [$location->id],
        'payment_type_id' => $paymentType->id,
        'counter_ids' => [$counter->id],
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
        'date' => [now()->format('Y-m-d'), now()->addDays(2)->format('Y-m-d')],
    ], $companyId);
    expect($response->first()->toArray())
        ->toHaveKey('payment_type.id', $paymentType->id)
        ->toHaveKey('payment_type.name', $paymentType->name)
        ->toHaveKey('total_count', 1)
        ->toHaveKey('total_amount', 10.00);
});

test('getPaymentTypeListForStoreManager method returns payment type list as expected.', function (): void {
    $companyId = Company::factory()->create()->id;
    $location = Location::factory()->create([
        'company_id' => $companyId,
        'name' => 'ABCD',
        'code' => 'XYZW',
    ]);
    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'happened_at' => now()->format('Y-m-d'),
        'layaway_pending_amount' => null,
        'status' => SaleStatus::REGULAR_SALE->value,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'sale_return_item_id' => null,
        'is_exchange' => false,
    ]);
    $paymentType = PaymentType::factory()->create([
        'company_id' => $companyId,
    ]);
    SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'payment_type_id' => $paymentType->id,
        'counter_update_id' => $counterUpdate->id,
        'amount' => 10,
    ]);
    $salePaymentQueries = resolve(SalePaymentQueries::class);
    $response = $salePaymentQueries->getPaymentTypeListForStoreManager([
        'payment_type_id' => $paymentType->id,
        'counter_ids' => [$counter->id],
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
        'date' => [now()->format('Y-m-d'), now()->addDays(2)->format('Y-m-d')],
    ], $location->id, $companyId);
    expect($response->first()->toArray())
        ->toHaveKey('payment_type.id', $paymentType->id)
        ->toHaveKey('payment_type.name', $paymentType->name);
});
