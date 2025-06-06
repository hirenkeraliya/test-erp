<?php

declare(strict_types=1);

namespace App\Domains\Barcode\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExportBarcodePdfGenerateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly int $exportRecordId,
        protected readonly int $companyId,
        protected readonly array $printItems
    ) {
    }

    public function handle(): void
    {
        $chunkedPrintItems = collect($this->printItems)->chunk(50)->toArray();

        $jobs = [];

        try {
            foreach ($chunkedPrintItems as $printItems) {
                $job = new CreateBarcodePdfJob($this->exportRecordId, $printItems, $this->companyId);
                $jobs[] = $job;
            }

            $jobs[] = new MergeBarcodePdfJob($this->exportRecordId, $this->companyId);

            Bus::chain($jobs)->onQueue('high')->dispatch();
        } catch (Throwable $throwable) {
            Log::error('Export Barcode Pdf Generation Job Error', [
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
