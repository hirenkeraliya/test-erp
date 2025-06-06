<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Models\CancelLayawaySale;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\Member;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditNote>
 */
class CreditNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'counter_update_id' => fn () => CounterUpdate::factory()->create()->id,
            'sale_return_id' => fn () => SaleReturn::factory()->create()->id,
            'cancel_layaway_sale_id' => fn () => CancelLayawaySale::factory()->create()->id,
            'member_id' => fn () => Member::factory()->create()->id,
            'expiry_date' => fake()->date(),
            'total_amount' => fake()->randomFloat(2, 0, 100),
            'available_amount' => fake()->randomFloat(2, 0, 100),
            'status' => array_rand(array_flip(array_column(CreditNoteStatuses::cases(), 'value'))),
        ];
    }
}
