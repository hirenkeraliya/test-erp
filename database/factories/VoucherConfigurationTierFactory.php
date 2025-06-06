<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherConfigurationTier>
 */
class VoucherConfigurationTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_configuration_id' => fn () => VoucherConfiguration::factory()->create()->id,
            'minimum_spend_amount' => random_int(1, 999),
            'maximum_spend_amount' => random_int(1, 999),
            'get_value' => random_int(1, 999),
        ];
    }
}
