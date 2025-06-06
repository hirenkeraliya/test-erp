<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'code' => fake()->uuid,
            'company_id' => fn () => Company::factory()->create()->id,
            'registration_number' => (string) fake()->randomDigit,
            'sst_number' => (string) fake()->randomDigit,
            'email' => fake()->unique()->safeEmail(),
            'phone' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'mobile' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'fax' => (string) fake()->randomNumber(),
            'address_line_1' => fake()->text($maxNbChars = 20),
            'address_line_2' => fake()->text($maxNbChars = 20),
            'city' => fake()->city(),
            'area_code' => (string) fake()->randomNumber(),
            'website' => fake()->url,
            'is_consignment' => false,
            'commission_percentage' => null,
        ];
    }
}
