<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\MemberGroup\Enums\DateConditionTypes;
use App\Domains\MemberGroup\Enums\ElementConditionTypes;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\Enums\NumberConditionTypes;
use App\Domains\MemberGroup\Enums\SmartGroupTypes;
use App\Models\Company;
use App\Models\MemberGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberGroup>
 */
class MemberGroupFactory extends Factory
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
            'name' => fake()->unique()->word(),
            'code' => fake()->uuid,
            'type_id' => array_rand(array_flip(array_column(GroupTypes::cases(), 'value'))),
            'smart_group_type_id' => array_rand(array_flip(array_column(SmartGroupTypes::cases(), 'value'))),
            'date_condition_type_id' => array_rand(array_flip(array_column(DateConditionTypes::cases(), 'value'))),
            'element_condition_type_id' => array_rand(
                array_flip(array_column(ElementConditionTypes::cases(), 'value'))
            ),
            'number_condition_type_id' => array_rand(array_flip(array_column(NumberConditionTypes::cases(), 'value'))),
            'date' => fake()->date(),
            'max_date' => fake()->date(),
            'value' => fake()->randomFloat(2, 0, 100),
            'max_value' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
