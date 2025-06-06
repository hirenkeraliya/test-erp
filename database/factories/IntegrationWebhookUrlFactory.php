<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\IntegrationWebhookUrls;
use App\Models\Integration;
use App\Models\IntegrationWebhookUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationWebhookUrl>
 */
class IntegrationWebhookUrlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'integration_id' => fn () => Integration::factory()->create()->getKey(),
            'webhook_url_type_id' => array_rand(array_flip(array_column(IntegrationWebhookUrls::cases(), 'value'))),
            'url' => fake()->url(),
        ];
    }
}
