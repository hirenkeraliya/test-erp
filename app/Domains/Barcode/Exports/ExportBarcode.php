<?php

declare(strict_types=1);

namespace App\Domains\Barcode\Exports;

use App\Domains\Barcode\Jobs\ExportBarcodePdfGenerateJob;
use App\Domains\Common\Enums\BarcodePrintModuleTypes;
use App\Domains\Common\Enums\BarcodePrintTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Models\ExportRecord;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\StockTransferItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExportBarcode implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $exportRecord = $exportRecordQueries->getFiltersById($exportRecordId, $companyId);
        $barcodeFilters = $exportRecord->filters;

        if (null === $barcodeFilters) {
            $this->markExportRecordAsFailed($exportRecord, $companyId);

            return;
        }

        $printItems = $this->getPrintItems($barcodeFilters, $companyId);

        $this->dispatchBarcodePdfGenerationJob($exportRecord->id, $companyId, $printItems);
    }

    private function markExportRecordAsFailed(ExportRecord $exportRecord, int $companyId): void
    {
        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $exportRecordQueries->markStatusAsFailed(
            $exportRecord->id,
            $companyId,
            Carbon::now()->format('Y-m-d H:i:s')
        );
    }

    private function getPrintItems(array $barcodeFilters, int $companyId): array
    {
        $stockTransferItemQueries = null;
        $stockTransferItems = null;
        if (array_key_exists(
            'module_type',
            $barcodeFilters
        ) && $barcodeFilters['module_type'] === BarcodePrintTypes::BY_MODULE->value) {
            if ($barcodeFilters['selected_module_by'] === BarcodePrintModuleTypes::GOODS_RECEIVED_NOTES->value) {
                $goodReceiveNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);

                $goodReceiveNoteProducts = $goodReceiveNoteProductQueries->getProductIdWithQuantity(
                    $barcodeFilters['reference_number'],
                    $companyId
                );

                return $this->transformGoodReceiveNoteProducts($goodReceiveNoteProducts);
            }

            $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
            $stockTransferItems = $stockTransferItemQueries->getProductIdWithQuantity(
                $barcodeFilters['reference_number'],
                (int) $barcodeFilters['selected_module_by'],
                $companyId
            );

            return $this->transformStockTransferItems($stockTransferItems);
        }

        return (array) $barcodeFilters['print_items'];
    }

    private function transformGoodReceiveNoteProducts(Collection $goodReceiveNoteProducts): array
    {
        $printItems = [];

        foreach ($goodReceiveNoteProducts as $goodReceiveNoteProduct) {
            /** @var GoodsReceivedNoteProduct $goodReceiveNoteProduct */
            $printItems[] = [
                'product_id' => $goodReceiveNoteProduct->product_id,
                'quantity' => $goodReceiveNoteProduct->quantity,
            ];
        }

        return $printItems;
    }

    private function transformStockTransferItems(Collection $stockTransferItems): array
    {
        $printItems = [];

        foreach ($stockTransferItems as $stockTransferItem) {
            /** @var StockTransferItem $stockTransferItem */
            $quantity = $stockTransferItem->received_quantity > 0 ? $stockTransferItem->received_quantity : $stockTransferItem->quantity;

            $printItems[] = [
                'product_id' => $stockTransferItem->product_id,
                'quantity' => $quantity,
            ];
        }

        return $printItems;
    }

    private function dispatchBarcodePdfGenerationJob(int $exportRecordId, int $companyId, array $printItems): void
    {
        ExportBarcodePdfGenerateJob::dispatch($exportRecordId, $companyId, $printItems)->onQueue('high');
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        return collect([]);
    }
}
