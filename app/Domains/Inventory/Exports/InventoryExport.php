<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\Inventory\DataPreparer\ExportDataPreparer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $inventories,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return ExportDataPreparer::prepareInventoryExportData($this->inventories, $this->filteredColumns);
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
