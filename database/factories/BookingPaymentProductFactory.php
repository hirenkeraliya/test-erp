<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BookingPayment;
use App\Models\BookingPaymentProduct;
use App\Models\BoxProduct;
use App\Models\PackageType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPaymentProduct>
 */
class BookingPaymentProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_payment_id' => fn () => BookingPayment::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => random_int(1, 1000),
            'box_product_id' => fn () => BoxProduct::factory()->create()->id,
            'product_box_package_type_id' => fn () => PackageType::factory()->create()->id,
            'product_box_units' => random_int(1, 1000),
        ];
    }
}
