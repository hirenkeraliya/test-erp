<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Product\Enums\ProductTypes;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\Template;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterProduct>
 */
class MasterProductFactory extends Factory
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
            'brand_id' => fn () => Brand::factory()->create()->id,
            'variant_template_id' => fn () => Template::factory()->create()->id,
            'name' => 'product ' . random_int(0, 10000) . fake()->word() . ' name ' . random_int(
                0,
                1000
            ) . fake()->randomNumber(),
            'code' => fake()->uuid,
            'description' => fake()->text(),
            'department_id' => fn () => Department::factory()->create()->id,
            'vendor_id' => null,
            'unit_of_measure_id' => fn () => UnitOfMeasure::factory()->create()->id,
            'article_number' => (string) fake()->randomNumber(),
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            'has_batch' => fake()->randomElement([true, false]),
            'is_non_inventory' => fake()->randomElement([true, false]),
            'is_non_selling_item' => fake()->randomElement([true, false]),
        ];
    }
}
