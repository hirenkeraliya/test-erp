<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Models\Company;
use App\Models\PromoterGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoterGroup>
 */
class PromoterGroupFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'code' => fake()->uuid,
            'type_id' => array_rand(array_flip(array_column(SaleReturnOrVoidSaleReasonTypes::cases(), 'value'))),
        ];
    }
}
