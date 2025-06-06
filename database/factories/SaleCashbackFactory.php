<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cashback;
use App\Models\CashMovement;
use App\Models\Sale;
use App\Models\SaleCashback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleCashback>
 */
class SaleCashbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => fn () => Sale::factory()->create()->id,
            'cashback_id' => fn () => Cashback::factory()->create()->id,
            'cash_movement_id' => fn () => CashMovement::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
