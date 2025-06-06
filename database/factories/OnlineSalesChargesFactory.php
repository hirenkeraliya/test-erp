<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use App\Models\Company;
use App\Models\OnlineSalesCharges;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnlineSalesCharges>
 */
class OnlineSalesChargesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'shipping_zone_id' => fn () => ShippingZone::factory()->create()->id,
            'company_id' => fn () => Company::factory()->create()->id,
            'shipping_charge_type_id' => array_rand(array_flip(array_column(ShippingChargeTypes::cases(), 'value'))),
            'name' => fake()->word(),
            'minimum_value' => fake()->randomFloat(2, 0, 100),
            'maximum_value' => fake()->randomFloat(2, 0, 100),
            'amount' => fake()->randomFloat(2, 0, 100),
            'status' => true,
            'is_available_in_ecommerce' => fake()->randomElement([true, false]),
        ];
    }
}
