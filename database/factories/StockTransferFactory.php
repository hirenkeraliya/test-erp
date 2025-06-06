<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\StockTransferReason;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'transfer_type' => array_rand(array_flip(array_column(StockTransferTypes::cases(), 'value'))),
            'stock_transfer_reason_id' => fn () => StockTransferReason::factory()->create()->id,
            'source_location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'destination_location_id' => fn () => Location::factory()->create([
                'type_id' => LocationTypes::STORE->value,
            ])->id,
            'transfer_date' => fake()->date('Y-m-d'),
            'attention' => fake()->name(),
            'requested_by_type' => ModelMapping::ADMIN->name,
            'requested_by_id' => fn () => Admin::factory()->create()->id,
            'reference_number' => fake()->randomNumber(),
            'remarks' => fake()->text(),
            'status' => array_rand(array_flip(array_column(StatusTypes::cases(), 'value'))),
        ];
    }

    public function openedAt(): static
    {
        return $this->state(fn (): array => [
            'opened_at' => Carbon::now(),
        ]);
    }

    public function approvedAt(): static
    {
        return $this->state(fn (): array => [
            'approved_at' => Carbon::now(),
        ]);
    }

    public function shippedAt(): static
    {
        return $this->state(fn (): array => [
            'shipped_at' => Carbon::now(),
        ]);
    }

    public function receivedAt(): static
    {
        return $this->state(fn (): array => [
            'received_at' => Carbon::now(),
        ]);
    }

    public function discrepancyAt(): static
    {
        return $this->state(fn (): array => [
            'discrepancy_at' => Carbon::now(),
        ]);
    }

    public function closedAt(): static
    {
        return $this->state(fn (): array => [
            'closedAt' => Carbon::now(),
        ]);
    }

    public function cancelledAt(): static
    {
        return $this->state(fn (): array => [
            'cancelled_at' => Carbon::now(),
        ]);
    }

    public function rejectedAt(): static
    {
        return $this->state(fn (): array => [
            'rejected_at' => Carbon::now(),
        ]);
    }
}
