<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Models\CashMovementReason;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovementReason>
 */
class CashMovementReasonFactory extends Factory
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
            'reason' => fake()->sentence(),
            'type_id' => array_rand(array_flip(array_column(CashMovementTypes::cases(), 'value'))),
        ];
    }
}
