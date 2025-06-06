<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferAverageLeadDays\Jobs\AggregatedAverageTransferDaysJob;
use App\Domains\StockTransferAverageLeadDays\StockTransferAverageLeadDaysQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->store = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->warehouse = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $this->stockTransfer = StockTransfer::factory()->make([
        'company_id' => $this->company->id,
        'source_location_id' => $this->store->id,
        'destination_location_id' => $this->warehouse->id,
        'requested_by_id' => 1,
        'stock_transfer_reason_id' => null,
        'status' => StatusTypes::getValueByCaseName('RECEIVED'),
        'received_at' => now()->format('Y-m-d h:i:s'),
        'shipped_at' => now()->subDay()->format('Y-m-d h:i:s'),
    ]);
});

test('AggregatedAverageTransferDaysJob job calls respective methods and store data into database', function (): void {
    $stockTransferAverageLeadDays = StockTransferAverageLeadDays::factory()->make([
        'from_location_id' => $this->store->id,
        'to_location_id' => $this->warehouse->id,
        'average_days' => 2,
    ]);

    $this->stockTransfer->average = 2;

    $this->mock(StockTransferQueries::class, function ($mock): void {
        $mock->shouldReceive('getGroupBySourceLocationIdAndType')
            ->once()
            ->andReturn(collect([$this->stockTransfer]));

        $mock->shouldReceive('getStockTransferListWithAverageDayBySourceLocationAndType')
            ->once()
            ->with((int) $this->stockTransfer->source_location_id)
            ->andReturn(collect([$this->stockTransfer]));
    });

    $this->mock(StockTransferAverageLeadDaysQueries::class, function ($mock) use (
        $stockTransferAverageLeadDays
    ): void {
        $mock->shouldReceive('updateOrCreate')
            ->once()
            ->andReturn($stockTransferAverageLeadDays);
    });

    AggregatedAverageTransferDaysJob::dispatch()->onQueue(config('horizon.default_queue_name'));
});
