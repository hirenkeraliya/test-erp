<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionChannelReference>
 */
class PromotionChannelReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'promotion_id' => fn () => Promotion::factory()->create()->id,
            'external_promotion_id' => random_int(1, 100),
        ];
    }
}
