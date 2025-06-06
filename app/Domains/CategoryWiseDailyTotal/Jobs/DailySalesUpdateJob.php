<?php

declare(strict_types=1);

namespace App\Domains\CategoryWiseDailyTotal\Jobs;

use App\Domains\Category\CategoryQueries;
use App\Domains\CategoryWiseDailyTotal\CategoryWiseDailyTotalQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailySalesUpdateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly Carbon $date;

    public function __construct(?string $date = null)
    {
        /** @var Carbon $date */
        $date = $date
            ? Carbon::createFromFormat('Y-m-d H:i:s', $date)
            : now();

        $this->date = $date;
    }

    public function handle(): void
    {
        Log::channel('daily_sales_update')->info('daily_sales_update', [
            'The start time of the Daily Sales Update job: ' . now()->format(
                'Y-m-d H:i:s'
            ) . ' Date : ' . $this->date->format('Y-m-d H:i:s'),
        ]);

        $startDate = $this->date->subHour()->startOfHour()->format('Y-m-d H:i:s');
        $endDate = $this->date->endOfHour()->format('Y-m-d H:i:s');

        $categoryWiseDailyTotalQueries = resolve(CategoryWiseDailyTotalQueries::class);

        $categoryQueries = resolve(CategoryQueries::class);
        $saleItemCountDetails = $categoryQueries->getSaleItemsTotalSum($startDate, $endDate);
        $saleReturnItemCountDetails = $categoryQueries->getSaleReturnItemsTotalSum($startDate, $endDate);

        DB::beginTransaction();

        try {
            foreach ($saleItemCountDetails as $saleItemCountDetail) {
                if (! $saleItemCountDetail->opened_by_pos_at) {
                    $saleItemCountDetail->opened_by_pos_at = $saleItemCountDetail->created_at;
                }

                /** @var Carbon $CounterOpenDate */
                $CounterOpenDate = Carbon::createFromFormat('Y-m-d H:i:s', $saleItemCountDetail->opened_by_pos_at);

                $categoryWiseDailyTotal = $categoryWiseDailyTotalQueries->getByCounterUpdateIdStoreIdCompanyIdAndDate(
                    $saleItemCountDetail->company_id,
                    (int) $saleItemCountDetail->location_id,
                    $saleItemCountDetail->counter_update_id,
                    $saleItemCountDetail->id,
                    $CounterOpenDate->format('Y-m-d')
                );

                if ($categoryWiseDailyTotal) {
                    $categoryWiseDailyTotalQueries->update(
                        $categoryWiseDailyTotal,
                        [
                            'total_units_sold' => $saleItemCountDetail->total_units_sold,
                            'total_amount' => $saleItemCountDetail->total_amount,
                            'total_units_return' => 0,
                            'total_amount_return' => 0,
                        ]
                    );

                    continue;
                }

                $categoryWiseDailyTotalQueries->addNew([
                    'company_id' => $saleItemCountDetail->company_id,
                    'location_id' => $saleItemCountDetail->location_id,
                    'category_id' => $saleItemCountDetail->id,
                    'counter_update_id' => $saleItemCountDetail->counter_update_id,
                    'date' => $CounterOpenDate->format('Y-m-d'),
                    'total_units_sold' => $saleItemCountDetail->total_units_sold,
                    'total_amount' => $saleItemCountDetail->total_amount,
                    'total_units_return' => 0,
                    'total_amount_return' => 0,
                ]);
            }

            foreach ($saleReturnItemCountDetails as $saleReturnItemCountDetail) {
                if (! $saleReturnItemCountDetail->opened_by_pos_at) {
                    $saleReturnItemCountDetail->opened_by_pos_at = $saleReturnItemCountDetail->created_at;
                }

                /** @var Carbon $CounterOpenDate */
                $CounterOpenDate = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $saleReturnItemCountDetail->opened_by_pos_at
                );

                $categoryWiseDailyTotal = $categoryWiseDailyTotalQueries->getByCounterUpdateIdStoreIdCompanyIdAndDate(
                    $saleReturnItemCountDetail->company_id,
                    $saleReturnItemCountDetail->location_id,
                    $saleReturnItemCountDetail->counter_update_id,
                    $saleReturnItemCountDetail->id,
                    $CounterOpenDate->format('Y-m-d'),
                );

                if ($categoryWiseDailyTotal) {
                    $categoryWiseDailyTotalQueries->update(
                        $categoryWiseDailyTotal,
                        [
                            'total_units_sold' => 0,
                            'total_amount' => 0,
                            'total_units_return' => $saleReturnItemCountDetail->total_return_units,
                            'total_amount_return' => $saleReturnItemCountDetail->total_return_sale_amount,
                        ]
                    );

                    continue;
                }

                $categoryWiseDailyTotalQueries->addNew([
                    'company_id' => $saleReturnItemCountDetail->company_id,
                    'location_id' => $saleReturnItemCountDetail->location_id,
                    'category_id' => $saleReturnItemCountDetail->id,
                    'counter_update_id' => $saleReturnItemCountDetail->counter_update_id,
                    'date' => $CounterOpenDate->format('Y-m-d'),
                    'total_units_sold' => 0,
                    'total_amount' => 0,
                    'total_units_return' => $saleReturnItemCountDetail->total_return_units,
                    'total_amount_return' => $saleReturnItemCountDetail->total_return_sale_amount,
                ]);
            }

            DB::commit();

            Log::channel('daily_sales_update')->info(
                'daily_sales_update',
                ['Daily Sales Update successfully processed.. Date : ' . $this->date->format('Y-m-d H:i:s')]
            );
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Daily Sales Update job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('daily_sales_update')->info('daily_sales_update', [
            'The Daily Sales Update job ended at: ' . now()->format('Y-m-d H:i:s') . ' Date : ' . $this->date->format(
                'Y-m-d H:i:s'
            ),
        ]);
    }
}
