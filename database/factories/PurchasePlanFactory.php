<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Models\Company;
use App\Models\Location;
use App\Models\PurchasePlan;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePlan>
 */
class PurchasePlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'reference_number' => fake()->uuid,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'company_id' => fn () => Company::factory()->create()->id,
            'vendor_id' => fn () => Vendor::factory()->create()->id,
            'remarks' => fake()->word(),
            'total_amount' => fake()->randomFloat(2, 1, 1000),
            'status' => Statuses::PENDING->value,
        ];
    }
}
