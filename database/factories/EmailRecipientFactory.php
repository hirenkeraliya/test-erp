<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmailRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailRecipient>
 */
class EmailRecipientFactory extends Factory
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
            'email_type_id' => fake()->randomElement([1, 2]),
            'receiver_name' => fake()->unique()->word(),
            'receiver_email' => fake()->safeEmail,
        ];
    }
}
