<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Admin;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransferItemTransaction>
 */
class StockTransferItemTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'stock_transfer_item_id' => fn () => StockTransferItem::factory()->create()->id,
            'remarks' => fake()->word(),
            'status' => StatusTypes::DRAFT->value,
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
