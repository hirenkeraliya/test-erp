<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Product\Enums\ProductTypes;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Company;
use App\Models\Department;
use App\Models\Product;
use App\Models\Season;
use App\Models\Size;
use App\Models\Style;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'name' => 'product ' . random_int(0, 10000) . fake()->word() . ' name ' . random_int(
                0,
                1000
            ) . fake()->randomNumber(),
            'description' => fake()->text(),
            'code' => fake()->uuid,
            'unit_of_measure_id' => fn () => UnitOfMeasure::factory()->create()->id,
            'season_id' => fn () => Season::factory()->create()->id,
            'department_id' => fn () => Department::factory()->create()->id,
            'sub_department_id' => fake()->randomElement([1, 2]),
            'color_id' => fn () => Color::factory()->create()->id,
            'size_id' => fn () => Size::factory()->create()->id,
            'brand_id' => fn () => Brand::factory()->create()->id,
            'style_id' => fn () => Style::factory()->create()->id,
            'upc' => fake()->uuid,
            'verification_qr_code' => fake()->regexify('[A-Z]{4}[0-9]{4}[A-Z]{3}'),
            'ean' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'custom_sku' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'manufacturer_sku' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'article_number' => (string) fake()->randomNumber(),
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
            'retail_price' => fake()->randomFloat(2, 0, 100),
            'franchise_price_1' => fake()->randomFloat(2, 0, 100),
            'franchise_price_2' => fake()->randomFloat(2, 0, 100),
            'franchise_price_3' => fake()->randomFloat(2, 0, 100),
            'wholesale_price' => fake()->randomFloat(2, 0, 100),
            'company_or_tender_price' => fake()->randomFloat(2, 0, 100),
            'branch_price' => fake()->randomFloat(2, 0, 100),
            'minimum_price' => fake()->randomFloat(2, 0, 100),
            'original_capital_price' => fake()->randomFloat(2, 0, 100),
            'capital_price' => fake()->randomFloat(2, 0, 100),
            'staff_price' => fake()->randomFloat(2, 0, 100),
            'purchase_cost' => fake()->randomFloat(2, 0, 100),
            'is_temporarily_unavailable' => fake()->randomElement([true, false]),
            'has_batch' => fake()->randomElement([true, false]),
            'is_non_inventory' => fake()->randomElement([true, false]),
            'is_non_selling_item' => fake()->randomElement([true, false]),
            'is_available_in_pos' => fake()->randomElement([true, false]),
            'is_available_in_ecommerce' => fake()->randomElement([true, false]),
            'is_sold_as_single_item' => fake()->randomElement([true, false]),
            'sell_item_via_derivative' => fake()->randomElement([true, false]),
            'online_price' => fake()->randomFloat(2, 0, 100),
            'vendor_id' => null,
        ];
    }
}
