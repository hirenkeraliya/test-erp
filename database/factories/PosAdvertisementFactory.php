<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class PosAdvertisementFactory extends Factory
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
            'type_id' => array_rand(array_flip(array_column(PosAdvertisementTypes::cases(), 'value'))),
            'name' => fake()->unique()->word(),
            'status' => true,
        ];
    }
}
