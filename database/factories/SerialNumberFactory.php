<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\SerialNumber\Enums\SerialNumberStatus;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Admin>
 */
class SerialNumberFactory extends Factory
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
            'product_id' => fn () => Product::factory()->create()->id,
            'serial_number' => fake()->word(),
            'status' => SerialNumberStatus::ACTIVE->value,
        ];
    }
}
