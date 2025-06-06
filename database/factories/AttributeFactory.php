<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Attribute\Enums\FieldType;
use App\Models\Attribute;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'field_type' => FieldType::TOGGLE->value,
            'is_required' => fake()->boolean(),
            'default_value' => (string) fake()->boolean(),
            'options' => null,
            'from' => null,
            'to' => null,
            'company_id' => fn () => Company::factory()->create()->id,
        ];
    }
}
