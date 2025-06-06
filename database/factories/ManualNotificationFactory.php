<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use App\Domains\ManualNotification\Enums\Statuses;
use App\Models\ManualNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualNotification>
 */
class ManualNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->sentence(),
            'message' => fake()->text(),
            'status' => array_rand(array_flip(array_column(Statuses::cases(), 'value'))),
            'type_id' => array_rand(array_flip(array_column(ManualNotificationTypes::cases(), 'value'))),
            'member_filter_type_id' => array_rand(array_flip(array_column(PromotersFilter::cases(), 'value'))),
            'promoter_filter_type_id' => array_rand(array_flip(array_column(PromotersFilter::cases(), 'value'))),
        ];
    }
}
