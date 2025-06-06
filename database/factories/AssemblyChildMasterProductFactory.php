<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssemblyChildMasterProduct;
use App\Models\MasterProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssemblyChildMasterProduct>
 */
class AssemblyChildMasterProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'master_product_id' => fn () => MasterProduct::factory()->create()->id,
            'child_master_product_id' => fn () => MasterProduct::factory()->create()->id,
            'units' => random_int(1, 9999),
        ];
    }
}
