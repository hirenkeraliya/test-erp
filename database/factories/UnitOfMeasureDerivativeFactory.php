<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitOfMeasureDerivative>
 */
class UnitOfMeasureDerivativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_of_measure_id' => fn () => UnitOfMeasure::factory()->create()->id,
            'name' => random_int(1, 1000) . fake()->word,
            'ratio' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
