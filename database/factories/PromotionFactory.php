<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Models\Company;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateTime = Carbon::now();

        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'name' => random_int(0, 1000) . fake()->word(),
            'promotion_applicable_type_id' => array_rand(
                array_flip(array_column(PromotionApplicableTypes::cases(), 'value'))
            ),
            'discount_type_id' => array_rand(array_flip(array_column(DiscountTypes::cases(), 'value'))),
            'cart_wide_promotion_type_id' => array_rand(
                array_flip(array_column(CartWidePromotionTypes::cases(), 'value'))
            ),
            'item_wise_promotion_type_id' => array_rand(
                array_flip(array_column(ItemWisePromotionTypes::cases(), 'value'))
            ),
            'timeframe_type_id' => array_rand(array_flip(array_column(PromotionTimeframeTypes::cases(), 'value'))),
            'percentage' => fake()->randomFloat(2, 0, 100),
            'flat_amount' => fake()->randomFloat(2, 0, 100),
            'start_date' => $dateTime->toDateString(),
            'end_date' => $dateTime->tomorrow()->toDateString(),
            'start_time' => $dateTime->toTimeString(),
            'end_time' => $dateTime->addHour()->toTimeString(),
            'allow_registered_member' => false,
            'allow_employee' => false,
            'allow_walk_in_member' => false,
            'status' => fake()->randomElement([true, false]),
            'dream_price_applicable' => fake()->randomElement([true, false]),
            'is_available_in_pos' => fake()->randomElement([true]),
            'is_available_in_ecommerce' => fake()->randomElement([false]),
        ];
    }
}
