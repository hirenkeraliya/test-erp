<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CashMovement;
use App\Models\SaleCashback;
use App\Models\SaleVoidCashback;
use App\Models\VoidSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleVoidCashback>
 */
class SaleVoidCashbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_cashback_id' => fn () => SaleCashback::factory()->create()->id,
            'void_sale_id' => fn () => VoidSale::factory()->create()->id,
            'cash_movement_id' => fn () => CashMovement::factory()->create()->id,
        ];
    }
}
