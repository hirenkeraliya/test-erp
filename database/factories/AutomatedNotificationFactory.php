<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Models\AutomatedNotification;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomatedNotification>
 */
class AutomatedNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(AutomatedNotificationTypes::cases(), 'value'))),
            'name' => fake()->word(),
            'description' => fake()->text(),
            'timeframe_type_id' => array_rand(
                array_flip(array_column(AutomatedNotificationTimeframeTypes::cases(), 'value'))
            ),
        ];
    }
}
