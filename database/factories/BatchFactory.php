<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'number' => fake()->uuid,
            'expiry_date' => fake()->date(),
            'notes' => fake()->text(),
            'external_id' => random_int(1, 9999),
        ];
    }
}
