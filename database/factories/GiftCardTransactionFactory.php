<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\GiftCardTransaction\Enums\GiftCardTransactionTypes;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCardTransaction>
 */
class GiftCardTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gift_card_id' => fn () => GiftCard::factory()->create()->id,
            'affected_by_id' => 1,
            'affected_by_type' => ModelMapping::SALE_PAYMENT->name,
            'type_id' => array_rand(array_flip(array_column(GiftCardTransactionTypes::cases(), 'value'))),
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
