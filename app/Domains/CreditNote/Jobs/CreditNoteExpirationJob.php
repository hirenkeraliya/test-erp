<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Jobs;

use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNoteExpiration\CreditNoteExpirationQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreditNoteExpirationJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('credit_note_expiration')->info('credit_note_expiration', [
            'Start time of the Credit Note Expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        DB::beginTransaction();

        try {
            $creditNoteExpirationQueries = resolve(CreditNoteExpirationQueries::class);
            $creditNoteQueries = resolve(CreditNoteQueries::class);
            $creditNotes = $creditNoteQueries->getActiveWithExpirationDue();

            foreach ($creditNotes as $creditNote) {
                $creditNoteQueries->markAseExpired($creditNote);

                $creditNoteExpirationQueries->addNew($creditNote->id, (float) $creditNote->available_amount);
            }

            DB::commit();

            Log::channel('credit_note_expiration')->info('credit_note_expiration', [
                $creditNotes->count() . ' Credit note expiration.',
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Credit note expiration job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('credit_note_expiration')->info('credit_note_expiration', [
            'The end time of the Credit Note Expiration job: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
