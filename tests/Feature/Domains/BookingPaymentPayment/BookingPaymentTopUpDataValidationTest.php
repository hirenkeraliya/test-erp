<?php

declare(strict_types=1);

use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Models\Company;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('Booking Payment Payment validations pass.', function (): void {
    $this->company = Company::factory()->create();

    $this->paymentType = PaymentType::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $request = new Request([
        'payment_type_id' => $this->paymentType->id,
        'amount' => 100,
        'remarks' => '',
    ]);

    $request->validate(BookingPaymentTopUpData::rules());
    $this->assertTrue(true);
});

test('Booking Payment Payment validations fails as expected.', function (): void {
    $request = new Request([
        'payment_type_id' => null,
        'amount' => null,
        'remarks' => '',
    ]);

    $request->validate(BookingPaymentTopUpData::rules());
})->throws(ValidationException::class);
