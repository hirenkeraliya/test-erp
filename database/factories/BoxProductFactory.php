<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PackageType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoxProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => fn () => Product::factory()->create()->id,
            'package_type_id' => fn () => PackageType::factory()->create()->id,
            'units' => random_int(1, 9999),
            'retail_price' => fake()->randomFloat(2, 0, 100),
            'staff_price' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
