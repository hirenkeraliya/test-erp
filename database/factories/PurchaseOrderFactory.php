<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'external_company_id' => fn () => ExternalCompany::factory()->create()->id,
            'external_location_id' => fn () => ExternalLocation::factory()->create()->id,
            'company_id' => fn () => Company::factory()->create()->id,
            'location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'reference_number' => fake()->uuid,
            'order_number' => fake()->uuid,
            'remarks' => fake()->word(),
            'attention' => fake()->word(),
            'require_date' => fake()->date(),
            'status' => Statuses::DRAFT->value,
            'order_type' => OrderTypes::PURCHASE_REQUEST->value,
            'created_by_company_id' => fn () => Company::factory()->create()->id,
            'external_order_number' => fake()->uuid,
        ];
    }
}
