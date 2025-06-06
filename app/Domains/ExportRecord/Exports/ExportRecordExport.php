<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Exports;

use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Models\ExportRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportRecordExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $exportRecords
    ) {
    }

    public function collection(): Collection
    {
        return $this->exportRecords->map(fn (ExportRecord $exportRecord): array => [
            'file_exported_at' => $exportRecord->created_at ? $exportRecord->created_at->format('d-m-Y h:i:s A') : null,
            'export_type' => ExportRecordTypes::getFormattedCaseName($exportRecord->type_id),
            'status' => ExportRecordStatuses::getFormattedCaseName($exportRecord->status),
            'total_records' => $exportRecord->total_records,
            'total_exported_records' => $exportRecord->total_exported_records,
        ]);
    }

    public function headings(): array
    {
        return ['File Exported At', 'Export Type', 'Status', 'Records', 'Exported Records'];
    }
}
