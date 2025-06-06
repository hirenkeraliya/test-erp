<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionRegeneration\Jobs;

use App\Domains\PromoterCommission\Jobs\PromoterCommissionGenerationChunkingJob;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PromoterCommissionRegenerationJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $promoterCommissionRegenerationId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('promoter_commission_generation')->info('promoter_commission_regeneration', [
            'The job of regenerating promoter commissions has started.',
        ]);

        try {
            $startOfPreviousMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');

            $promoterCommissionQueries = resolve(PromoterCommissionQueries::class);
            $promoterCommissions = $promoterCommissionQueries->getIdsByPeriod($startOfPreviousMonth);

            DB::beginTransaction();

            $promoterCommissionRegenerationQueries = resolve(PromoterCommissionRegenerationQueries::class);
            $promoterCommissionRegenerationQueries->markAsStarted(
                $this->promoterCommissionRegenerationId,
                now()->format('Y-m-d H:i:s')
            );

            $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);
            $promoterCommissionUpdateQueries->deleteByPromoterCommissionIds(
                $promoterCommissions->pluck('id')->toArray()
            );

            $promoterCommissionQueries->deleteByPeriod($startOfPreviousMonth);

            DB::commit();

            PromoterCommissionGenerationChunkingJob::dispatch()->onQueue('high');

            Log::channel('promoter_commission_generation')->info('promoter_commission_regeneration', [
                'The regeneration of promoter commission is finished.',
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Promoter commission regeneration failed', [
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
