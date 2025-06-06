<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'name' => fake()->randomElement(
                ['Delivery Truck', 'Cargo Van', 'Box Truck', 'Pickup Truck', 'Flatbed Truck']
            ),
            'plate_no' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'type_of_vehicle' => fake()->optional(0.8)->randomElement(
                ['Truck', 'Van', 'Car', 'Motorcycle', 'Heavy Duty Truck', 'Light Duty Truck']
            ),
            'status' => true,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => fn () => Admin::factory()->create()->id,
        ];
    }
}
