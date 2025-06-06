<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceivedNote>
 */
class GoodsReceivedNoteFactory extends Factory
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
            'vendor_id' => fn () => Vendor::factory()->create()->id,
            'grn_reference' => fake()->unique()->word . random_int(1, 1000),
            'purchase_order_reference' => fake()->word . random_int(1, 1000),
            'delivery_order_reference' => fake()->word . random_int(1, 1000),
            'notes' => fake()->paragraph,
        ];
    }
}
