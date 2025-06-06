<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Models\Admin;
use App\Models\SaleChannel;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Size>
 */
class SyncTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_channel_id' => fn () => SaleChannel::factory()->create()->id,
            'type_id' => array_rand(array_flip(array_column(SyncTypes::cases(), 'value'))),
            'user_id' => fn () => Admin::factory()->create()->id,
            'user_type' => ModelMapping::ADMIN->name,
        ];
    }
}
