<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Exports;

use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\Inventory\DataPreparer\ExportDataPreparer;
use App\Domains\Inventory\InventoryQueries;
use App\Models\ExportRecord;
use Illuminate\Support\Collection;

class ExportInventories implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        $inventoryQueries = resolve(InventoryQueries::class);

        $inventories = $inventoryQueries->exportInventoryRecords(
            $exportRecord->filters ?? [],
            $exportRecord->company_id,
            $insertedRows,
            $nextRecords
        );

        $headerColumns = collect($exportRecord->headers);

        return ExportDataPreparer::prepareInventoryExportData($inventories, $headerColumns);
    }
}
