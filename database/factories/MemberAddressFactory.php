<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberAddress>
 */
class MemberAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => fn () => Member::factory()->create()->id,
            'name' => fake()->name(),
            'contact_mobile_number' => (string) fake()->numberBetween(1_00_000_000_000, 9_99_999_999_999),
            'contact_email' => fake()->safeEmail,
            'address_line_1' => fake()->streetName,
            'address_line_2' => fake()->streetAddress,
            'city' => fake()->city,
            'area_code' => fake()->postcode,
            'is_primary' => true,
        ];
    }
}
