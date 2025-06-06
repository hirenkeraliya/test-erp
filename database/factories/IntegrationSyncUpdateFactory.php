<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Integration;
use App\Models\IntegrationSyncUpdate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationSyncUpdate>
 */
class IntegrationSyncUpdateFactory extends Factory
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
            'module_type' => ModelMapping::PRODUCT->name,
            'last_sync_date' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
