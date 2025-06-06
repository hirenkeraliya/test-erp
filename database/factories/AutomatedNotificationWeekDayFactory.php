<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutomatedNotification;
use App\Models\AutomatedNotificationWeekDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomatedNotificationWeekDay>
 */
class AutomatedNotificationWeekDayFactory extends Factory
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
            'week_day' => random_int(1, 7),
        ];
    }
}
