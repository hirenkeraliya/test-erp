<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Product;
use App\Models\PurchaseAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceivedNoteProduct>
 */
class GoodsReceivedNoteProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goods_received_note_id' => fn () => GoodsReceivedNote::factory()->create()->id,
            'product_id' => fn () => Product::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'quantity' => random_int(1, 1000),
        ];
    }
}
