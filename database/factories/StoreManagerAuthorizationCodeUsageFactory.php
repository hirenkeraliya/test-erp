<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Models\Sale;
use App\Models\StoreManagerAuthorizationCode;
use App\Models\StoreManagerAuthorizationCodeUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreManagerAuthorizationCodeUsage>
 */
class StoreManagerAuthorizationCodeUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'store_manager_authorization_code_id' => fn () => StoreManagerAuthorizationCode::factory()->create(),
            'usage_type_id' => StoreManagerAuthorizationCodeUsageTypes::SALE_ITEM_PRICE_OVERRIDE->value,
            'reference_id' => fn () => Sale::factory()->create(),
            'reference_type' => ModelMapping::SALE->name,
        ];
    }
}
