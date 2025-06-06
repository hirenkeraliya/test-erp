<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Models\VoidSaleReason;
use App\Models\VoidSaleReasonType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoidSaleReasonType>
 */
class VoidSaleReasonTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'void_sale_reason_id' => fn () => VoidSaleReason::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(SaleReturnOrVoidSaleReasonTypes::cases(), 'value'))),
        ];
    }
}
