<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerModules;
use App\Domains\AggregateProcessTracker\Enums\AggregateProcessTrackerStatuses;
use App\Models\AggregateProcessTracker;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AggregateProcessTracker>
 */
class AggregateProcessTrackerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->getKey(),
            'job_type' => array_rand(array_flip(array_column(AggregateProcessTrackerModules::cases(), 'value'))),
            'status' => array_rand(array_flip(array_column(AggregateProcessTrackerStatuses::cases(), 'value'))),
            'last_refreshed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
