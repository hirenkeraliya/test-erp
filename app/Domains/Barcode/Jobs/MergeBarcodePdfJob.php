<?php

declare(strict_types=1);

namespace App\Domains\Barcode\Jobs;

use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\Storage\Enums\StorageTypes;
use App\Domains\Storage\Services\StorageService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class MergeBarcodePdfJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly int $exportRecordId,
        protected readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        $pdfFilesPath = collect(Storage::files('barcode_print/' . $this->exportRecordId));

        $storageService = resolve(StorageService::class);

        try {
            $oMerger = PDFMerger::init();

            if ($pdfFilesPath->count() === 0) {
                $exportRecordQueries = resolve(ExportRecordQueries::class);
                $exportRecordQueries->markStatusAsFailed(
                    $this->exportRecordId,
                    $this->companyId,
                    now()->format('Y-m-d H:i:s')
                );

                return;
            }

            foreach ($pdfFilesPath as $pdfFilePath) {
                $localFilePath = $storageService->getLocalFilePath($pdfFilePath);
                $oMerger->addPDF($localFilePath, 'all');
            }

            Storage::disk(StorageTypes::LOCAL->value)->makeDirectory('barcode_print/' . $this->exportRecordId);

            $mergedPdfFilePath = storage_path(
                'app/barcode_print/' . $this->exportRecordId . '/merged-barcode-' . $this->exportRecordId . '.pdf'
            );

            $oMerger->merge();
            $oMerger->save($mergedPdfFilePath);

            $exportRecordQueries = resolve(ExportRecordQueries::class);
            $exportRecordQueries->addMedia($mergedPdfFilePath, $this->exportRecordId, $this->companyId);

            Storage::deleteDirectory('barcode_print/' . $this->exportRecordId);

            $exportRecordQueries->markAsCompletedAndJobEndedAt(
                $this->exportRecordId,
                $this->companyId,
                Carbon::now()->format('Y-m-d H:i:s')
            );
        } catch (Throwable $throwable) {
            Log::error('Merge Barcode Job Error', [
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
