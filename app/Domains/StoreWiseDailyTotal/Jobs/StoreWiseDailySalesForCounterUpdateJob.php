<?php

declare(strict_types=1);

namespace App\Domains\StoreWiseDailyTotal\Jobs;

use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreWiseDailySalesForCounterUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $counterUpdateId
    ) {
    }

    public function handle(): void
    {
        Log::channel('daily_store_wise_sales')->info('daily_store_wise_sales', [
            'The start time for the Daily Store Wise Sales For Counter job: ' . now()->format(
                'Y-m-d H:i:s'
            ) . ' counter update id : ' . $this->counterUpdateId,
        ]);

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $storeWiseSales = $saleQueries->getDailyStoreWiseDataForCounterUpdate($this->counterUpdateId);

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $storeWiseDailyReturnTotals = $saleReturnQueries->getDailyStoreWiseDataForCounterUpdate($this->counterUpdateId);

        DB::beginTransaction();

        try {
            foreach ($storeWiseSales as $storeWiseSale) {
                if (! $storeWiseSale->opened_by_pos_at) {
                    $storeWiseSale->opened_by_pos_at = $storeWiseSale->created_at;
                }

                /** @var Carbon $counterOpenDate */
                $counterOpenDate = Carbon::createFromFormat('Y-m-d H:i:s', $storeWiseSale->opened_by_pos_at);

                $storeWiseDailyTotal = $storeWiseDailyTotalQueries->getByCounterUpdateIdStoreIdCompanyIdAndDate(
                    $storeWiseSale->company_id,
                    $storeWiseSale->location_id,
                    $storeWiseSale->brand_id,
                    $storeWiseSale->counter_update_id,
                    $counterOpenDate->format('Y-m-d'),
                );

                if ($storeWiseDailyTotal) {
                    $storeWiseDailyTotalQueries->updateSales(
                        $storeWiseDailyTotal,
                        [
                            'total_sales_count' => $storeWiseSale->total_sales_count,
                            'total_units_sold' => $storeWiseSale->total_units_sold,
                            'total_sales_amount' => $storeWiseSale->total_sales_amount,
                        ]
                    );

                    continue;
                }

                $storeWiseDailyTotalQueries->addNew([
                    'company_id' => $storeWiseSale->company_id,
                    'location_id' => $storeWiseSale->location_id,
                    'brand_id' => $storeWiseSale->brand_id,
                    'counter_update_id' => $storeWiseSale->counter_update_id,
                    'date' => $counterOpenDate->format('Y-m-d'),
                    'total_sales_count' => $storeWiseSale->total_sales_count,
                    'total_units_sold' => $storeWiseSale->total_units_sold,
                    'total_sales_amount' => $storeWiseSale->total_sales_amount,
                    'total_units_return' => 0,
                    'total_amount_return' => 0,
                ]);
            }

            foreach ($storeWiseDailyReturnTotals as $storeWiseDailyReturnTotal) {
                if (! $storeWiseDailyReturnTotal->opened_by_pos_at) {
                    $storeWiseDailyReturnTotal->opened_by_pos_at = $storeWiseDailyReturnTotal->created_at;
                }

                /** @var Carbon $CounterOpenDate */
                $CounterOpenDate = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $storeWiseDailyReturnTotal->opened_by_pos_at
                );

                $storeWiseDailyTotal = $storeWiseDailyTotalQueries->getByCounterUpdateIdStoreIdCompanyIdAndDate(
                    $storeWiseDailyReturnTotal->company_id,
                    $storeWiseDailyReturnTotal->location_id,
                    $storeWiseDailyReturnTotal->brand_id,
                    $storeWiseDailyReturnTotal->counter_update_id,
                    $CounterOpenDate->format('Y-m-d')
                );

                if ($storeWiseDailyTotal) {
                    $storeWiseDailyTotalQueries->updateReturns(
                        $storeWiseDailyTotal,
                        [
                            'total_units_return' => $storeWiseDailyReturnTotal->total_units_return,
                            'total_amount_return' => $storeWiseDailyReturnTotal->total_sale_return_amount,
                        ]
                    );

                    continue;
                }

                $storeWiseDailyTotalQueries->addNew([
                    'company_id' => $storeWiseDailyReturnTotal->company_id,
                    'location_id' => $storeWiseDailyReturnTotal->location_id,
                    'brand_id' => $storeWiseDailyReturnTotal->brand_id,
                    'counter_update_id' => $storeWiseDailyReturnTotal->counter_update_id,
                    'date' => $CounterOpenDate->format('Y-m-d'),
                    'total_sales_count' => 0,
                    'total_units_sold' => 0,
                    'total_sales_amount' => 0,
                    'total_units_return' => $storeWiseDailyReturnTotal->total_units_return,
                    'total_amount_return' => $storeWiseDailyReturnTotal->total_sale_return_amount,
                ]);
            }

            DB::commit();

            Log::channel('daily_store_wise_sales')->info(
                'daily_store_wise_sales',
                [
                    'Daily Store Wise Sales For Counter Update job finished. counter update id : ' . $this->counterUpdateId,
                ]
            );
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Daily Store Wise Sales Update job error:', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('daily_store_wise_sales')->info('daily_store_wise_sales', [
            'The job end time for the Daily Store Wise Sales for counter update is: ' . now()->format(
                'Y-m-d H:i:s'
            ) . ' counter update id : ' . $this->counterUpdateId,
        ]);
    }
}
