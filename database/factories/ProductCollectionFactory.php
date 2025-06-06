<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Models\Company;
use App\Models\ProductCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCollection>
 */
class ProductCollectionFactory extends Factory
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
            'name' => fake()->name(),
            'number_of_products' => 0,
            'pending_products' => 0,
            'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
            'status' => true,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => 1,
            'is_available_in_ecommerce' => fake()->randomElement([true, false]),
        ];
    }
}
