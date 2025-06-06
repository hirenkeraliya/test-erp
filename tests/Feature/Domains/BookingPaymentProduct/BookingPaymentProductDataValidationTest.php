<?php

declare(strict_types=1);

use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('Booking Payment Product validations pass.', function (): void {
    $this->company = Company::factory()->create();

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $request = new Request([
        'promoter_ids' => [1],
        'products' => [
            0 => [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ],
        ],
    ]);

    $request->validate(BookingPaymentProductData::rules());
    $this->assertTrue(true);
});

test('Booking Payment Product validations fail as expected.', function (): void {
    $request = new Request([
        'products' => [
            0 => [
                'product_id' => 1,
            ],
        ],
    ]);

    $request->validate(BookingPaymentProductData::rules());
})->throws(ValidationException::class);
