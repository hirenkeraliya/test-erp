<?php

declare(strict_types=1);

namespace App\Domains\StockTransferAverageLeadDays;

use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\StockTransfer;
use App\Models\StockTransferAverageLeadDays;

class StockTransferAverageLeadDaysQueries
{
    public function updateOrCreate(StockTransfer $stockTransfer): void
    {
        /** @var int $average */
        $average = (int) ceil((float) $stockTransfer['average']);

        $stockTransferAverageLeadDaysId = StockTransferAverageLeadDays::updateOrCreate(
            [
                'from_location_id' => (int) $stockTransfer->source_location_id,
                'to_location_id' => (int) $stockTransfer->destination_location_id,
            ],
            [
                'average_days' => $average,
            ]
        )->id;

        if (null !== $stockTransfer->stock_transfer_average_lead_day_id) {
            return;
        }

        unset($stockTransfer['average']);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferQueries->updateAverageLeadyDay($stockTransferAverageLeadDaysId, $stockTransfer);
    }

    public function getAverageAggregateDays(array $validatedData): int
    {
        $stockTransferAverageLeadDay = StockTransferAverageLeadDays::query()
            ->select('average_days')
            ->where('from_location_id', $validatedData['source_location_id'])
            ->where('to_location_id', $validatedData['destination_location_id'])
            ->first();

        return $stockTransferAverageLeadDay ? $stockTransferAverageLeadDay->average_days : 0;
    }

    public function getIdByLocation(int $fromLocationId, int $toLocationId): ?int
    {
        return StockTransferAverageLeadDays::query()
            ->select('id')
            ->where('from_location_id', $fromLocationId)
            ->where('to_location_id', $toLocationId)
            ->first()?->id;
    }

    public function getAverageDaysColumn(): string
    {
        return 'id,average_days';
    }
}
