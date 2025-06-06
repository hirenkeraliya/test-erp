<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CancelLayawaySale;
use App\Models\Sale;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CancelLayawaySale>
 */
class CancelCreditSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_id' => fn () => Sale::factory()->create()->id,
            'store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'reason' => fake()->unique()->word(),
        ];
    }
}
