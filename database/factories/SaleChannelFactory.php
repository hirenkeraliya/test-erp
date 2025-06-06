<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\Company;
use App\Models\Location;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleChannel>
 */
class SaleChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'code' => fake()->name(),
            'company_id' => fn () => Company::factory()->create()->getKey(),
            'default_location_id' => fn (array $attributes) => Location::factory()->create([
                'company_id' => $attributes['company_id'],
                'type_id' => LocationTypes::STORE->value,
            ])->getKey(),
            'type_id' => array_rand(array_flip(array_column(SaleChannelTypes::cases(), 'value'))),
            'inventory_deduct_order_status' => array_rand(array_flip(array_column(OrderStatus::cases(), 'value'))),
            'status' => true,
            'display_variants' => true,
        ];
    }
}
