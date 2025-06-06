<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalProduct>
 */
class ExternalProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $generateUpc = random_int(0, 1);

        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'external_company_id' => fn () => ExternalCompany::factory()->create()->id,
            'product_name' => fake()->unique()->word(),
            'upc' => 0 !== $generateUpc ? fake()->regexify('[1-4]{3}') : fake()->regexify('[A-Z]{5}[1-4]{3}'),
            'product_details' => [
                fake()->word => fake()->word,
            ],
            'status' => array_rand(array_flip(array_column(ExternalProductStatuses::cases(), 'value'))),
            'approved_by_id' => fn () => Admin::factory()->create()->id,
            'approved_by_type' => ModelMapping::ADMIN->name,
            'approved_at' => fake()->date(),
        ];
    }
}
