<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sale;
use App\Models\StoreManager;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoidSale>
 */
class VoidSaleFactory extends Factory
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
            'void_sale_number' => (string) fake()->randomNumber(),
            'voided_by_store_manager_id' => fn () => StoreManager::factory()->create()->id,
            'void_sale_reason_id' => fn () => VoidSaleReason::factory()->create()->id,
        ];
    }
}
