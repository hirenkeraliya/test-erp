<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\AuthorizerTypes;
use App\Models\SaleItem;
use App\Models\SaleItemComplimentary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemComplimentary>
 */
class SaleItemComplimentaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $authorizerType = array_rand(array_flip(array_column(AuthorizerTypes::cases(), 'value')));

        $authorizerClass = AuthorizerTypes::getAuthorizerTypeClass($authorizerType);

        return [
            'sale_item_id' => fn () => SaleItem::factory()->create()->id,
            'authorizer_id' => fn () => $authorizerClass::factory()->create()->id,
            'authorizer_type' => $authorizerClass,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
