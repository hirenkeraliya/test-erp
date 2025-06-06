<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTake>
 */
class StockTakeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_record_date' => fake()->date('Y-m-d'),
            'company_id' => fn () => Company::factory()->create()->id,
            'requested_by_id' => fn () => StoreManager::factory()->create()->id,
            'requested_by_type' => ModelMapping::STORE_MANAGER->name,
            'location_id' => fn () => Location::factory()->create()->id,
            'notes' => fake()->text(10),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'submitted_by_id' => fn () => StoreManager::factory()->create()->id,
            'submitted_by_type' => ModelMapping::STORE_MANAGER->name,
            'submitted_at' => Carbon::now(),
        ]);
    }
}
