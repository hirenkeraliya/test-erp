<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DraftProductTransaction>
 */
class DraftProductTransactionFactory extends Factory
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
            'approved_by_id' => fn () => Admin::factory()->create()->id,
            'approved_by_type' => ModelMapping::ADMIN->name,
            'approved_at' => fake()->date(),
        ];
    }
}
