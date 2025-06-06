<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ExportRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportRecord>
 */
class ExportRecordFactory extends Factory
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
            'type_id' => fake()->randomNumber(),
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => fn () => Admin::factory()->create()->id,
            'filters' => json_encode([fake()->word], JSON_THROW_ON_ERROR),
            'job_queued_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'job_started_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'job_ended_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => ExportRecordStatuses::PENDING->value,
            'job_id' => fake()->uuid(),
        ];
    }
}
