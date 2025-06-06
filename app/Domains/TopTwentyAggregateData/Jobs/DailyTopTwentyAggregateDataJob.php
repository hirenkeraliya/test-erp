<?php

declare(strict_types=1);

namespace App\Domains\TopTwentyAggregateData\Jobs;

use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyTopTwentyAggregateDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    private readonly string $date;

    public function __construct(?string $date = null)
    {
        /** @var string $date */
        $date = $date ?: Carbon::yesterday()->format('Y-m-d');

        $this->date = $date;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('daily_top_twenty_report_update_job')->info('daily_top_twenty_report_update_job', [
            'The start time of the Daily Top Twenty Report Update job: ' . now()->format('Y-m-d H:i:s'),
            'Date: ' .  $this->date,
        ]);

        $saleItemRecords = $this->getTopTwentyAggregateRecords();

        $topTwentyAggregateDataQueries = resolve(TopTwentyAggregateDataQueries::class);

        DB::beginTransaction();

        try {
            foreach ($saleItemRecords as $saleItemRecord) {
                /** @var Sale $sale */
                $sale = $saleItemRecord->sale;

                $topTwentyAggregateDataQueries->addNew([
                    'product_id' => $saleItemRecord->product_id,
                    'counter_update_id' => $sale->counter_update_id,
                    'date' => $sale->happened_at,
                    'quantity' => $saleItemRecord->quantity,
                    'gross_sales' => $saleItemRecord->total_price_paid + $saleItemRecord->total_discount_amount,
                    'discount' => $saleItemRecord->total_discount_amount,
                    'net_sales' => $saleItemRecord->total_price_paid,
                    'tax' => $saleItemRecord->total_tax_amount,
                    'total_amount' => $saleItemRecord->total_tax_amount + $saleItemRecord->total_price_paid,
                ]);
            }

            DB::commit();

            Log::channel('daily_top_twenty_report_update_job')->info('daily_top_twenty_report_update_job', [
                'The end time of the Daily Top Twenty Report Update job: ' . now()->format('Y-m-d H:i:s'),
                'Date: ' .  $this->date,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('daily_top_twenty_report_update_job:')->error('daily_top_twenty_report_update_job', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            $this->fail($throwable);

            Log::channel('daily_top_twenty_report_update_job')->error('daily_top_twenty_report_update_job', [
                'Error: The Daily Top Twenty Report Update job: ' . now()->format('Y-m-d H:i:s'),
                'Date: ' .  $this->date,
            ]);
        }
    }

    private function getTopTwentyAggregateRecords(): Collection
    {
        $saleItemQueries = resolve(SaleItemQueries::class);

        return $saleItemQueries->getTopTwentyAggregateData([$this->date, $this->date]);
    }
}
