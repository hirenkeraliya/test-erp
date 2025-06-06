<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherTransaction>
 */
class VoucherTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_id' => fn () => Voucher::factory()->create()->id,
            'action_type_id' => array_rand(array_flip(array_column(VoucherTransactionActionTypes::cases(), 'value'))),
            'happened_at' => fake()->dateTime(),
        ];
    }
}
