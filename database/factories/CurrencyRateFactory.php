<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencyRate>
 */
class CurrencyRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'currency_id' => fn () => Currency::factory()->create()->id,
            'rate' => fake()->randomFloat(2, 0, 10),
        ];
    }
}
