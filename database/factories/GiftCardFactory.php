<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\GiftCard\Enums\GiftCardStatuses;
use App\Domains\GiftCard\Enums\GiftCardTypes;
use App\Models\Company;
use App\Models\GiftCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCard>
 */
class GiftCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $randomFloat = fake()->randomFloat(2, 0, 100);

        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(GiftCardTypes::cases(), 'value'))),
            'number' => $randomFloat . fake()->randomNumber() . $randomFloat + 1,
            'expiry_date' => fake()->date(),
            'total_amount' => $randomFloat,
            'available_amount' => $randomFloat,
            'status' => array_rand(array_flip(array_column(GiftCardStatuses::cases(), 'value'))),
        ];
    }
}
