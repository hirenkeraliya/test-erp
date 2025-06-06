<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreManager>
 */
class StoreManagerFactory extends Factory
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
            'username' => fake()->userName,
            'password' => bcrypt('123456'),
            'passcode' => '123456',
            'price_override_type' => array_rand(array_flip(array_column(PriceOverrideTypes::cases(), 'value'))),
            'can_manage_wholesale' => fake()->randomElement([true, false]),
            'remember_token' => null,
            'forgot_password_token' => null,
            'forgot_password_token_expiration_at' => null,
            'price_override_limit_percentage_for_item' => fake()->randomFloat(2, 0, 100),
            'price_override_limit_percentage_for_cart' => fake()->randomFloat(2, 0, 100),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ];
    }
}
