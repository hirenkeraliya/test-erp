<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderCreditNoteTransaction\Enums\OrderCreditNoteType;
use App\Models\Location;
use App\Models\OrderCreditNote;
use App\Models\OrderCreditNoteTransaction;
use App\Models\OrderPayment;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderCreditNoteTransaction>
 */
class OrderCreditNoteTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_credit_note_id' => fn () => OrderCreditNote::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(OrderCreditNoteType::cases(), 'value'))),
            'store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'order_payment_id' => fn () => OrderPayment::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
