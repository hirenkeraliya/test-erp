<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderStatus;
use App\Models\EcommerceLocation;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EcommerceLocation>
 */
class EcommerceLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'url' => fake()->url(),
            'client_secret' => fake()->word(),
            'inventory_deduct_order_status' => OrderStatus::ACCEPTED->value,
        ];
    }
}
