<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationProduct;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomatedNotificationProduct>
 */
class AutomatedNotificationProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'automated_notification_id' => fn () => AutomatedNotification::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'low_stock_alert_threshold' => random_int(1, 9999),
        ];
    }
}
