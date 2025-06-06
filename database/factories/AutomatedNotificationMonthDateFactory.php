<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationMonthDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomatedNotificationMonthDate>
 */
class AutomatedNotificationMonthDateFactory extends Factory
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
            'month_date' => random_int(1, 31),
        ];
    }
}
