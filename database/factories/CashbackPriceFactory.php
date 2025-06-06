<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Cashback\Enums\ConditionTypes;
use App\Models\Cashback;
use App\Models\CashbackPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashbackPrice>
 */
class CashbackPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'cashback_id' => fn () => Cashback::factory()->create()->id,
            'condition_operator_type_id' => ConditionTypes::LESS_THAN->value,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
