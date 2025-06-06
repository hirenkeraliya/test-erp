<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\NegotiatorTypes;
use App\Models\Sale;
use App\Models\SaleItemPriceOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemPriceOverride>
 */
class SalePriceOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $negotiatorTypes = array_rand(array_flip(array_column(NegotiatorTypes::cases(), 'value')));

        $negotiatorClass = NegotiatorTypes::getNegotiatorClass($negotiatorTypes);

        return [
            'sale_id' => fn () => Sale::factory()->create()->id,
            'negotiator_type' => $negotiatorTypes,
            'negotiator_id' => fn () => $negotiatorClass::factory()->create()->id,
            'override_price' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
