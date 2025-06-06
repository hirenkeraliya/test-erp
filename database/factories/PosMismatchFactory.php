<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\PosMismatch;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosMismatch>
 */
class PosMismatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => fn () => Sale::factory()->create()->id,
            'module_type' => ModelMapping::SALE->name,
            'message' => fake()->text(),
        ];
    }
}
