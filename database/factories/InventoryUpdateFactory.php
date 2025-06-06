<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Batch;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseAmount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryUpdate>
 */
class InventoryUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string
     * mixed> =>
     */
    public function definition(): array
    {
        $typeId = 0 !== random_int(0, 1) ? LocationTypes::STORE->value : LocationTypes::WAREHOUSE->value;

        return [
            'product_id' => fn () => Product::factory()->create()->id,
            'batch_id' => fn () => Batch::factory()->create()->id,
            'purchase_amount_id' => fn () => PurchaseAmount::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => $typeId,
            ])->id,
            'affected_by_id' => 1,
            'affected_by_type' => ModelMapping::CASHIER->name,
            'quantity' => random_int(0, 100),
            'user_id' => 1,
            'user_type' => ModelMapping::ADMIN->name,
            'happened_at' => fake()->date(),
            'notes' => fake()->text(),
            'closing_stock' => random_int(0, 100),
        ];
    }
}
