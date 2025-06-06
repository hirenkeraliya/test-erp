<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\Department;
use App\Models\PromoterCommission;
use App\Models\PromoterCommissionUpdate;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoterCommissionUpdate>
 */
class PromoterCommissionUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promoter_commission_id' => fn () => PromoterCommission::factory()->create()->id,
            'affected_by_id' => fn () => SaleItem::factory()->create()->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'department_id' => fn () => Department::factory()->create()->id,
            'commission_amount' => fake()->randomFloat(2, 0, 100),
            'commission_percentage' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
