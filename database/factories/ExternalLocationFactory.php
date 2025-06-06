<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\ExternalCompany;
use App\Models\ExternalLocation;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalLocation>
 */
class ExternalLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $typeId = array_rand(array_flip(array_column(LocationTypes::cases(), 'value')));

        return [
            'external_company_id' => fn () => ExternalCompany::factory()->create()->id,
            'external_location_id' => fn () => Location::factory()->create([
                'type_id' => $typeId,
            ])->id,
            'type_id' => $typeId,
            'name' => fake()->word(),
            'code' => fake()->uuid,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->address(),
            'address_line_2' => fake()->address(),
            'city' => fake()->city(),
            'area_code' => (string) random_int(0o00000, 999999),
        ];
    }
}
