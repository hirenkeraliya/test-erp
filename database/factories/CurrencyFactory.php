<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => fn () => Country::factory()->create()->id,
            'name' => fake()->name(),
            'code' => Str::random(3),
            'precision' => random_int(0, 2),
            'symbol' => Str::random(2),
            'symbol_native' => Str::random(2),
            'symbol_first' => '1',
            'decimal_mark' => '.',
            'thousands_separator' => ',',
        ];
    }
}
