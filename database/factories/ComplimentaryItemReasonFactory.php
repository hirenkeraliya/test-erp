<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ComplimentaryItemReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplimentaryItemReason>
 */
class ComplimentaryItemReasonFactory extends Factory
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
            'reason' => fake()->unique()->sentence(),
        ];
    }
}
