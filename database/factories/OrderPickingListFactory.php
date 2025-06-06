<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\OrderPickingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPickingList>
 */
class OrderPickingListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'number' => fake()->randomDigit(),
        ];
    }
}
