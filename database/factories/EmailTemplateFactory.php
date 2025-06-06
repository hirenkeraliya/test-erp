<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'usage' => fake()->numberBetween(0, 100),
            'clicks' => fake()->numberBetween(100, 10000),
            'revenue' => fake()->randomFloat(2, 0, 100),
            'conversion' => fake()->numberBetween(0, 100),
            'template_json' => '[]',
            'html' => 'test',
        ];
    }
}
