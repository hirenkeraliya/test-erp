<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\RetailPlanningHierarchy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetailPlanningHierarchy>
 */
class RetailPlanningHierarchyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->word(),
            'parent_id' => null,
            'company_id' => fn () => Company::factory()->create()->id,
        ];
    }

    public function childOf($parentId)
    {
        return $this->state(fn (): array => [
            'parent_id' => $parentId,
        ]);
    }
}
