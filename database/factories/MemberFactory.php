<?php

declare(strict_types=1);

namespace Database\Factories;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
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
            'type_id' => array_rand(array_flip(array_column(Types::cases(), 'value'))),
            'title_id' => array_rand(array_flip(array_column(Titles::cases(), 'value'))),
            'race_id' => array_rand(array_flip(array_column(Races::cases(), 'value'))),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName,
            'gender_id' => array_rand(array_flip(array_column(Genders::cases(), 'value'))),
            'date_of_birth' => fake()->date(),
            'mobile_number' => (string) fake()->numberBetween(1_00_000_000_000, 9_99_999_999_999),
            'email' => fake()->safeEmail,
            'company_name' => fake()->company,
            'company_registration_number' => (string) random_int(111_111_111, 999_999_999),
            'company_tax_number' => (string) random_int(111_111_111, 999_999_999),
            'company_address' => fake()->streetName,
            'company_phone' => (string) fake()->numberBetween(1_00_000_000_000, 9_99_999_999_999),
            'pic_name' => fake()->lastName,
            'pic_contact' => (string) fake()->numberBetween(1_00_000_000_000, 9_99_999_999_999),
            'created_by_id' => 1,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_location_id' => fn () => Location::factory()->create()->id,
            'last_purchase_date' => fake()->dateTime(),
            'notes' => fake()->text(),
            'card_number' => CommonFunctions::getTwelveDigitNumber(),
            'is_azentio_member' => false,
        ];
    }
}
