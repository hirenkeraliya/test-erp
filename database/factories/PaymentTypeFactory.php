<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\PaymentType\Enums\PaymentTypeImages;
use App\Models\Company;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentType>
 */
class PaymentTypeFactory extends Factory
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
            'name' => fake()->unique()->word,
            'parent_payment_type_id' => null,
            'is_member_required' => random_int(0, 1),
            'is_available_for_refund' => random_int(0, 1),
            'trigger_card_payment_machine' => random_int(0, 1),
            'trigger_qr_code_payment_machine' => random_int(0, 1),
            'trigger_card_affin_payment_machine' => random_int(0, 1),
            'is_card_payment' => random_int(0, 1),
            'status' => true,
            'image_name' => array_rand(array_flip(array_column(PaymentTypeImages::cases(), 'value'))),
            'payment_terminal_key' => fake()->sentence(),
            'trigger_card_bank_rakyat_terminal' => random_int(0, 1),
            'site_key' => Str::random(32),
            'secret_key' => Str::random(32),
            'is_available_in_ecommerce' => fake()->randomElement([true, false]),
            'is_available_in_pos' => fake()->randomElement([true, false]),
        ];
    }
}
