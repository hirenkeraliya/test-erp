<?php

declare(strict_types=1);

namespace App\Domains\Barcode\Jobs;

use App\Domains\Barcode\Services\BarcodeServices;
use App\Domains\Common\Enums\BarcodePrintSizes;
use App\Domains\ExportRecord\ExportRecordQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateBarcodePdfJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly int $exportRecordId,
        protected readonly array $printItems,
        protected readonly int $companyId,
    ) {
    }

    public function handle(): void
    {
        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $exportRecord = $exportRecordQueries->getFiltersById($this->exportRecordId, $this->companyId);
        $filters = $exportRecord->filters;

        if (! $filters) {
            return;
        }

        $productIds = collect($this->printItems)->pluck('product_id')->toArray();

        $barcodeServices = resolve(BarcodeServices::class);
        $products = $barcodeServices->prepareProductsPrint(
            $this->companyId,
            $productIds,
            $filters['print_columns'],
            collect($this->printItems),
            $filters['product_price']
        );

        try {
            foreach ($products as $product) {
                $fileName = 'barcode-' . $this->exportRecordId . '-' . $product->id . '.pdf';

                Storage::makeDirectory('barcode_print/' . $this->exportRecordId);
                $filePath = 'barcode_print/' . $this->exportRecordId . '/' . $fileName;

                if (array_key_exists(
                    'print_size',
                    $filters
                ) && $filters['print_size'] === BarcodePrintSizes::PRINT_SIZE_ONE->value) {
                    $barcodeServices->generatePrintSizeOnePdf($filePath, collect([$product]), $filters['remark']);
                }

                if (array_key_exists(
                    'print_size',
                    $filters
                ) && $filters['print_size'] === BarcodePrintSizes::PRINT_SIZE_TWO->value) {
                    $barcodeServices->generatePrintSizeTwoPdf($filePath, collect([$product]), $filters['remark']);
                }

                if (array_key_exists(
                    'print_size',
                    $filters
                ) && $filters['print_size'] === BarcodePrintSizes::PRINT_SIZE_THREE->value) {
                    $barcodeServices->generatePrintSizeThreePdf($filePath, collect([$product]), $filters['remark']);
                }

                if (! array_key_exists('print_size', $filters)) {
                    continue;
                }

                if ($filters['print_size'] !== BarcodePrintSizes::PRINT_SIZE_FOUR->value) {
                    continue;
                }

                $barcodeServices->generatePrintSizeFourPdf($filePath, collect([$product]), $filters['remark']);
            }
        } catch (Throwable $throwable) {
            Log::error('Create Barcode PDF Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'line' => 'Line: ' . $throwable->getLine(),
                'file' => 'File: ' . $throwable->getFile(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
