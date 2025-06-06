<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Exports;

use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Models\ImportRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportRecordExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $importRecords
    ) {
    }

    public function collection(): Collection
    {
        return $this->importRecords->map(fn (ImportRecord $importRecord): array => [
            'file_uploaded_at' => $importRecord->created_at ? $importRecord->created_at->format('d-m-Y h:i:s A') : null,
            'import_type' => ImportTypes::getFormattedCaseName($importRecord->type_id),
            'status' => Status::getFormattedCaseName($importRecord->status),
            'records_imported' => $importRecord->records_imported,
            'records_in_file' => $importRecord->records_in_file,
            'records_failed' => $importRecord->records_failed,
        ]);
    }

    public function headings(): array
    {
        return [
            'File Uploaded At',
            'Import Type',
            'Status',
            'Records Imported',
            'Records In File',
            'Records Failed',
        ];
    }
}
