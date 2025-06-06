<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use App\Models\CreditNoteVoidUse;
use App\Models\VoidSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditNoteVoidUse>
 */
class CreditNoteVoidUseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_note_id' => fn () => CreditNote::factory()->create()->id,
            'credit_note_uses_id' => fn () => CreditNoteUse::factory()->create()->id,
            'void_sale_id' => fn () => VoidSale::factory()->create()->id,
            'amount' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
