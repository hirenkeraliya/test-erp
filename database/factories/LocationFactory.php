<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Store\Enums\StoreTimings;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $typeId = array_rand(array_flip(array_column(LocationTypes::cases(), 'value')));

        return [
            'name' => fake()->name(),
            'type_id' => $typeId,
            'code' => fake()->uuid,
            'company_id' => fn () => Company::factory()->create()->id,
            'registration_number' => (string) fake()->randomDigit(),
            'sst_number' => (string) fake()->randomDigit(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => (string) fake()->numberBetween(1_00_000_000_000, 9_99_999_999_999),
            'mobile' => (string) fake()->numberBetween(1_00_000_000_000, 9_99_999_999_999),
            'fax' => (string) fake()->randomNumber(),
            'address_line_1' => fake()->text($maxNbChars = 20),
            'address_line_2' => fake()->text($maxNbChars = 20),
            'area_code' => (string) fake()->randomNumber(),
            'web_site' => $typeId === LocationTypes::STORE->value ? fake()->url() : null,
            'sales_tax_percentage' => $typeId === LocationTypes::STORE->value ? fake()->randomFloat(2, 0, 100) : null,
            'sales_return_days_limit' => $typeId === LocationTypes::STORE->value ? fake()->numberBetween(0, 100) : null,
            'credit_note_expiration_days' => $typeId === LocationTypes::STORE->value ? fake()->numberBetween(
                0,
                100
            ) : null,
            'loyalty_point_expiration_days' => $typeId === LocationTypes::STORE->value ? fake()->numberBetween(
                0,
                100
            ) : null,
            'is_automatic_day_close' => 0,
            'automatic_day_close_time' => null,
            'receipt_footer' => $typeId === LocationTypes::STORE->value ? fake()->text($maxNbChars = 10) : null,
            'disclaimer' => $typeId === LocationTypes::STORE->value ? fake()->text($maxNbChars = 10) : null,
            'cash_out_limit_info' => 0.00,
            'cash_out_limit_warning' => 0.00,
            'cash_out_limit_restrict' => 0.00,
            'open_time' => $typeId === LocationTypes::STORE->value ? StoreTimings::OPEN_TIME->value : null,
            'close_time' => $typeId === LocationTypes::STORE->value ? StoreTimings::CLOSE_TIME->value : null,
            'share_inventory_to_external_companies' => false,
        ];
    }
}
