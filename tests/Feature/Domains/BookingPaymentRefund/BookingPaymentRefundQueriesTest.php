<?php

declare(strict_types=1);

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentRefund\BookingPaymentRefundQueries;
use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\BookingPaymentRefund;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Currency;
use App\Models\Location;
use App\Models\PaymentType;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->cashier = Cashier::factory()->create();
    $this->location = Location::factory()->create([
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

    $this->cashier->counter_update_id = $this->counterUpdate->id;
    $this->cashier->save();

    $this->bookingPayment = BookingPayment::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $this->bookingPaymentRefundQueries = new BookingPaymentRefundQueries();
});

test('the addNew method adds the booking payment refund data', function (): void {
    BookingPaymentPayment::factory()->create([
        'booking_payment_id' => $this->bookingPayment->id,
    ]);

    $currency = Currency::factory()->create();

    $this->company = Company::factory()->create();

    $this->paymentType = PaymentType::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Cash',
    ]);

    $validateData = [
        'amount' => 10,
        'payment_type_id' => $this->paymentType->id,
        'currency_id' => $currency->id,
        'current_currency_rate' => 1,
        'currency_amount' => 1,
    ];

    $bookingPaymentRefundData = new BookingPaymentRefundData(...$validateData);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $this->cashier);

    $bookingPaymentDetails = [
        'booking_payment_id' => $this->bookingPayment->id,
        'counter_update_id' => $this->cashier->counter_update_id,
        'payment_type_id' => $bookingPaymentRefundData->payment_type_id,
        'amount' => 100,
        'currency_id' => $bookingPaymentRefundData->currency_id,
        'currency_rate' => $bookingPaymentRefundData->current_currency_rate,
        'currency_amount' => $bookingPaymentRefundData->currency_amount,
    ];

    $this->bookingPaymentRefundQueries->addNew($bookingPaymentDetails);

    $this->assertDatabaseHas('booking_payment_refunds', [
        'amount' => 100,
        'booking_payment_id' => $this->bookingPayment->id,
        'currency_id' => $currency->id,
        'currency_rate' => 1,
        'currency_amount' => 1,
    ]);
});

test(
    'the getByCounterUpdateIdWithPaymentType method returns the booking payment refunds with payment type by counter update id',
    function (): void {
        $payment = PaymentType::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $bookingPaymentRefund = BookingPaymentRefund::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
            'counter_update_id' => $this->cashier->counter_update_id,
            'payment_type_id' => $payment->id,
        ]);

        $response = $this->bookingPaymentRefundQueries->getByCounterUpdateIdWithPaymentType(
            $this->cashier->counter_update_id,
        );

        expect($response->first()->toArray())
            ->toHaveKey('payment_type_id', $bookingPaymentRefund->payment_type_id)
            ->toHaveKey('amount', $bookingPaymentRefund->amount)
            ->toHaveKey('payment_type');
    }
);
