<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\SaleReturnReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturnReason>
 */
class SaleReturnReasonFactory extends Factory
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
            'reason' => fake()->sentence(),
            'location_id' => null,
            'put_back_in_inventory' => true,
        ];
    }
}
