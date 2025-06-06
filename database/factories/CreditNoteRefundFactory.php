<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\CreditNoteRefund;
use App\Models\PaymentType;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditNoteRefund>
 */
class CreditNoteRefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_note_id' => fn () => CreditNote::factory()->create()->id,
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'payment_type_id' => fn () => PaymentType::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
            'store_manager_id' => fn () => StoreManager::factory()->create()->id,
        ];
    }
}
