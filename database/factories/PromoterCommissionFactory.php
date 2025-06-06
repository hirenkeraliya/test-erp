<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends <PromoterCommission>
 */
class PromoterCommissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promoter_id' => fn () => Promoter::factory()->create()->id,
            'commission_amount' => fake()->randomFloat(2, 0, 100),
            'total_sales_amount' => fake()->randomFloat(2, 0, 100),
            'monthly_sales_target' => fake()->randomFloat(2, 0, 100),
            'commission_date' => Carbon::now()->toDateString(),
        ];
    }
}
