<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Models\SaleReturnReason;
use App\Models\SaleReturnReasonType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturnReasonType>
 */
class SaleReturnReasonTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_return_reason_id' => fn () => SaleReturnReason::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(SaleReturnOrVoidSaleReasonTypes::cases(), 'value'))),
        ];
    }
}
