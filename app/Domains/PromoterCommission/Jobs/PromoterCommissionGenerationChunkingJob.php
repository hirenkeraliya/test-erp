<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Jobs;

use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PromoterCommissionGenerationChunkingJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly Carbon $commissionGenerationDate;

    public function __construct(?string $commissionGenerationDate = null)
    {
        /** @var Carbon $commissionGenerationDate */
        $commissionGenerationDate = $commissionGenerationDate
            ? Carbon::createFromFormat('Y-m-d H:i:s', $commissionGenerationDate)
            : now()->subMonthNoOverflow();

        $this->commissionGenerationDate = $commissionGenerationDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('promoter_commission_generation')->info('promoter_commission_generation_chunking', [
            'The job for generating promoter commissions has started chunking. Date: ' . $this->commissionGenerationDate,
        ]);

        /** @var Carbon $commissionGenerationDate */
        $commissionGenerationDate = $this->commissionGenerationDate;

        $firstDayOfPreviousMonth = $commissionGenerationDate->startOfMonth()->format('Y-m-d');

        $promoterCommissionQueries = resolve(PromoterCommissionQueries::class);

        if ($promoterCommissionQueries->entryExistsForPeriod($firstDayOfPreviousMonth)) {
            info(
                'First Day of the Previous Month: ' . $firstDayOfPreviousMonth .
                'Message: The promoter commission entry for the previous month already exists' .
                'Date: ' . $this->commissionGenerationDate
            );

            throw new Exception('The promoter commission entry for the previous month already exists.');
        }

        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getIds();

        foreach ($promoters->chunk(30) as $chunkPromoters) {
            PromoterCommissionGenerationJob::dispatch($chunkPromoters->pluck('id')->toArray())->onQueue('high');
        }

        Log::channel('promoter_commission_generation')->info('promoter_commission_generation_chunking', [
            'The promoter commission generation chunking job has finished. Date: ' . $this->commissionGenerationDate,
        ]);
    }
}
