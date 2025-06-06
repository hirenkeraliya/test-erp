<?php

declare(strict_types=1);

use App\Domains\BookingPaymentRefund\DataObjects\BookingPaymentRefundData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Member;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('Booking Payment Refund validations pass.', function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location,
    ]);

    $this->paymentType = PaymentType::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Cash',
    ]);

    $currency = Currency::factory()->create();

    $request = new Request([
        'amount' => 100,
        'payment_type_id' => $this->paymentType->id,
        'currency_id' => $currency->id,
        'current_currency_rate' => 1,
        'currency_amount' => 1,
    ]);

    $request->validate(BookingPaymentRefundData::rules());
    $this->assertTrue(true);
});

test(
    'Booking Payment Refund throws exception due to payment type id does not exists in our records',
    function (): void {
        $request = new Request([
            'amount' => 100,
            'payment_type_id' => 1,
            'currency_id' => 1,
            'current_currency_rate' => 1,
            'currency_amount' => 1,
        ]);
        $request->validate(BookingPaymentRefundData::rules());
    }
)->throws(ValidationException::class);
