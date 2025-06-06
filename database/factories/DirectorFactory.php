<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\Director;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Director>
 */
class DirectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => fn () => Employee::factory()->create()->id,
            'passcode' => '123456',
            'price_override_type' => array_rand(array_flip(array_column(PriceOverrideTypes::cases(), 'value'))),
            'price_override_limit_percentage_for_item' => fake()->randomFloat(2, 0, 100),
            'price_override_limit_percentage_for_cart' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
