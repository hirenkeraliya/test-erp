<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\MysteryGift\Enums\Statuses;
use App\Models\Company;
use App\Models\MysteryGift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MysteryGift>
 */
class MysteryGiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'name' => fake()->unique()->word(),
            'min_flat_amount' => random_int(0, 100),
            'max_flat_amount' => random_int(0, 100),
            'min_percentage' => random_int(0, 100),
            'max_percentage' => random_int(0, 100),
            'is_flat_amount' => random_int(0, 1),
            'is_percentage' => random_int(0, 1),
            'is_free_product' => random_int(0, 1),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'minimum_spend' => random_int(0, 100),
            'minimum_spend_amount_for_free_product' => random_int(0, 100),
            'minimum_spend_amount_for_flat_amount' => random_int(0, 100),
            'minimum_spend_amount_for_percentage' => random_int(0, 100),
            'status' => array_rand(array_flip(array_column(Statuses::cases(), 'value'))),
        ];
    }
}
