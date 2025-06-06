<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePlanTransaction>
 */
class PurchasePlanTransactionFactory extends Factory
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
            'old_status' => fake()->boolean(),
            'new_status' => fake()->boolean(),
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
