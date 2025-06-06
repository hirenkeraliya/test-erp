<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Banner\Enums\ActionTypes;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'description' => fake()->text($maxNbChars = 20),
            'action_type_id' => ActionTypes::CUSTOM_URL->value,
            'custom_url' => fake()->url(),
            'status' => true,
        ];
    }
}
