<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MasterProduct;
use App\Models\MasterProductChannelReference;
use App\Models\SaleChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterProductChannelReference>
 */
class MasterProductChannelReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'master_product_id' => fn () => MasterProduct::factory()->create()->id,
            'external_master_product_id' => random_int(1, 100),
        ];
    }
}
