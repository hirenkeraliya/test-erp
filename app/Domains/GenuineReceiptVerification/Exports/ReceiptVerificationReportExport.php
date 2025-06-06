<?php

declare(strict_types=1);

namespace App\Domains\GenuineReceiptVerification\Exports;

use App\Domains\Common\Services\ExportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReceiptVerificationReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $genuineReceiptVerifications,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->genuineReceiptVerifications->map(function ($genuineReceiptVerification): array {
            $genuineReceiptVerificationData = [
                'name' => $genuineReceiptVerification->name,
                'mobile_number' => $genuineReceiptVerification->mobile_number,
                'email' => $genuineReceiptVerification->email,
                'is_genuine' => $genuineReceiptVerification->is_genuine ? 'Genuine' : 'Fake',
                'receipt_number' => $genuineReceiptVerification->receipt_number,
                'created_at' => $genuineReceiptVerification->created_at->format('d-m-Y D h:s:i A'),
                'remarks' => $genuineReceiptVerification->remarks,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($genuineReceiptVerificationData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
