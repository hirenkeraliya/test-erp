<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\SaleChannel;
use App\Models\SaleChannelWebhookUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleChannelWebhookUrl>
 */
class SaleChannelWebhookUrlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->getKey(),
            'webhook_url_type_id' => array_rand(array_flip(array_column(SaleChannelTypes::cases(), 'value'))),
            'url' => fake()->url(),
        ];
    }
}
