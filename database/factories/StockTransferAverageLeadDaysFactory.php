<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Models\Location;
use App\Models\StockTransferAverageLeadDays;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransferAverageLeadDays>
 */
class StockTransferAverageLeadDaysFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $typeId = 0 !== random_int(0, 1) ? LocationTypes::STORE->value : LocationTypes::WAREHOUSE->value;

        return [
            'from_location_id' => fn () => Location::factory()->create([
                'type_id' => $typeId,
            ])->id,
            'to_location_id' => fn () => Location::factory()->create([
                'type_id' => $typeId,
            ])->id,
            'average_days' => random_int(1, 9999),
        ];
    }
}
