<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\VoidSaleReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoidSaleReason>
 */
class VoidSaleReasonFactory extends Factory
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
            'reason' => random_int(1, 100) . fake()->word,
        ];
    }
}
