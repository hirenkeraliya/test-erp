<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransferAverageLeadDays\StockTransferAverageLeadDaysQueries;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;

beforeEach(function (): void {
    $this->store = Location::factory([
        'type_id' => LocationTypes::STORE->value,
    ])->create();

    $this->warehouse = Location::factory([
        'type_id' => LocationTypes::WAREHOUSE->value,
    ])->create();

    $this->stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);
});

test('stock transfer average lead days can be added', function (): void {
    $stockTransfer = StockTransfer::factory()->create([
        'id' => 1,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::getValueByCaseName('RECEIVED'),
        'received_at' => now()->format('Y-m-d h:i:s'),
        'opened_at' => now()->subDay()->format('Y-m-d h:i:s'),
    ]);

    $stockTransfer['average'] = 2;

    $this->stockTransferAverageLeadDaysQueries->updateOrCreate($stockTransfer);
    $stockTransferAverageLeadDaysId = $this->stockTransferAverageLeadDaysQueries->getIdByLocation(
        $stockTransfer->source_location_id,
        $stockTransfer->destination_location_id
    );

    $this->assertDatabaseHas('stock_transfer_average_lead_days', [
        'from_location_id' => $stockTransfer->source_location_id,
        'to_location_id' => $stockTransfer->destination_location_id,
        'average_days' => 2,
    ]);

    $this->assertDatabaseHas('stock_transfers', [
        'id' => $stockTransfer->id,
        'stock_transfer_average_lead_day_id' => $stockTransferAverageLeadDaysId,
    ]);
});

test('call getAverageAggregateDays method return average day as expected', function (): void {
    $stockTransferAverageLeadDays = StockTransferAverageLeadDays::factory()->create([
        'from_location_id' => $this->store->id,
        'to_location_id' => $this->warehouse->id,
        'average_days' => 2,
    ]);

    $validateData = [
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
    ];

    $response = $this->stockTransferAverageLeadDaysQueries->getAverageAggregateDays($validateData);
    expect($response)->toBe($stockTransferAverageLeadDays->average_days);
});

test('call getAverageAggregateDays method return blank when not found', function (): void {
    $validateData = [
        'source_location_id' => 10,
        'destination_location_id' => 20,
    ];

    $response = $this->stockTransferAverageLeadDaysQueries->getAverageAggregateDays($validateData);
    expect($response)->toBe(0);
});

test('call getIdByLocation method return id or null by from & to location', function (): void {
    $stockTransferAverageLeadDays = StockTransferAverageLeadDays::factory()->create();
    $response = $this->stockTransferAverageLeadDaysQueries->getIdByLocation(
        $stockTransferAverageLeadDays->from_location_id,
        $stockTransferAverageLeadDays->to_location_id
    );
    expect($response)->toBe($stockTransferAverageLeadDays->id);

    $response = $this->stockTransferAverageLeadDaysQueries->getIdByLocation(0, 0o1);
    expect($response)->toBe(null);
});
