<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->word(),
            'company_id' => fn () => Company::factory()->create()->id,
            'loyalty_point' => random_int(0, 100),
            'minimum_point' => $min = random_int(0, 100),
            'maximum_point' => random_int($min, 100),
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => fn () => Admin::factory()->create()->id,
            'type' => array_rand(array_flip(array_column(RewardTypes::cases(), 'value'))),
            'target_type' => array_rand(array_flip(array_column(RewardTargetTypes::cases(), 'value'))),
            'discount_type' => array_rand(array_flip(array_column(DiscountTypes::cases(), 'value'))),
            'discount' => random_int(0, 100),
            'status' => fake()->boolean(),
        ];
    }
}
