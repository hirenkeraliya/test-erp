<?php

declare(strict_types=1);

namespace App\Domains\StockTransferAverageLeadDays\Jobs;

use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StockTransferAverageLeadDays\StockTransferAverageLeadDaysQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AggregatedAverageTransferDaysJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        Log::channel('average_transfer_day')->info('average_transfer_day', [
            'The start time of the Average transfer day job: ' . now()->format(
                'Y-m-d H:i:s'
            ) . ' Date : ' . now()->format('Y-m-d H:i:s'),
        ]);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransferAverageLeadDaysQueries = resolve(StockTransferAverageLeadDaysQueries::class);
        $stockTransfers = $stockTransferQueries->getGroupBySourceLocationIdAndType();

        DB::beginTransaction();
        try {
            foreach ($stockTransfers as $stockTransfer) {
                $stockTransferListData = $stockTransferQueries->getStockTransferListWithAverageDayBySourceLocationAndType(
                    (int) $stockTransfer->source_location_id,
                );
                foreach ($stockTransferListData as $stockTransferData) {
                    $stockTransferAverageLeadDaysQueries->updateOrCreate($stockTransferData);
                }
            }

            DB::commit();
            Log::channel('average_transfer_day')->info(
                'average_transfer_day',
                ['Average transfer day successfully processed.. Date : ' . now()->format('Y-m-d H:i:s')]
            );
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Average transfer day job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
