<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderCreditNote;
use App\Models\OrderReturn;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderCreditNote>
 */
class OrderCreditNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'order_return_id' => fn () => OrderReturn::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'expiry_date' => fake()->date(),
            'total_amount' => fake()->randomFloat(2, 0, 100),
            'available_amount' => fake()->randomFloat(2, 0, 100),
            'status' => array_rand(array_flip(array_column(CreditNoteStatuses::cases(), 'value'))),
        ];
    }
}
