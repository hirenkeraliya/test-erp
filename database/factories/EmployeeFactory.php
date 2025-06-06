<?php

declare(strict_types=1);

namespace Database\Factories;

use App\CommonFunctions;
use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
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
            'designation_id' => fn () => Designation::factory()->create()->id,
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_number' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'home_contact' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'address_line_1' => fake()->text($maxNbChars = 20),
            'address_line_2' => fake()->text($maxNbChars = 20),
            'city' => fake()->city(),
            'area_code' => (string) fake()->randomNumber(),
            'date_of_joining' => fake()->date(),
            'primary_contact_name' => fake()->name(),
            'primary_contact_phone' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'staff_id' => (string) Str::uuid(),
            'ic_number' => (string) fake()->randomDigit,
            'job_type' => fake()->randomElement([1, 2]),
            'card_number' => CommonFunctions::getTwelveDigitNumber(),
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => false,
        ]);
    }
}
