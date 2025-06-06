<?php

declare(strict_types=1);

namespace App\Domains\Barcode\Exports;

use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Models\ExportRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BarcodeRecordExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $exportRecords
    ) {
    }

    public function collection(): Collection
    {
        return $this->exportRecords->map(fn (ExportRecord $exportRecord): array => [
            'export_record_type' => ExportRecordTypes::getFormattedCaseName($exportRecord->type_id),
            'created_by_type' => $exportRecord->created_by_type,
            'status' => ExportRecordStatuses::getFormattedCaseName($exportRecord->status),
        ]);
    }

    public function headings(): array
    {
        return ['Export Record Type', 'Created By', 'Status'];
    }
}
