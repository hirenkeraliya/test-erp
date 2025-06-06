<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Cashback;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashback>
 */
class CashbackFactory extends Factory
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
            'exclude_by_type' => array_rand(array_flip(array_column(ExcludeByTypes::cases(), 'value'))),
            'discount_type_id' => array_rand(array_flip(array_column(DiscountTypes::cases(), 'value'))),
            'discount_value' => fake()->randomFloat(2, 0, 100),
            'name' => fake()->word(),
            'minimum_spend_amount' => fake()->randomFloat(2, 0, 100),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
        ];
    }
}
