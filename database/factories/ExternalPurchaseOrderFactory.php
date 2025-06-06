<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ExternalPurchaseOrder\Enums\Statuses;
use App\Models\PurchasePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalPurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'purchase_plan_id' => fn () => PurchasePlan::factory()->create()->id,
            'order_number' => $this->faker->word,
            'date' => $this->faker->date(),
            'notes' => $this->faker->text,
            'fob' => $this->faker->randomFloat(2, 0, 999999.99),
            'freight_charges' => $this->faker->randomFloat(2, 0, 999999.99),
            'insurance_charges' => $this->faker->randomFloat(2, 0, 999999.99),
            'duty' => $this->faker->randomFloat(2, 0, 999999.99),
            'sst' => $this->faker->randomFloat(2, 0, 999999.99),
            'handling_charges' => $this->faker->randomFloat(2, 0, 999999.99),
            'other_charges' => $this->faker->randomFloat(2, 0, 999999.99),
            'total_amount' => $this->faker->randomFloat(2, 0, 999999.99),
            'status' => Statuses::PENDING->value,
        ];
    }
}
