<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Exports;

use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockTransferStatusSummaryExportExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected array $stockTransfersData,
        protected array $dateRange,
        protected Company $company,
        protected Collection $locations,
        protected array $filterData,
    ) {
    }

    public function view(): View
    {
        return view('prints.stock_transfer_status_by_summary_for_export', [
            'stockTransfers' => $this->stockTransfersData,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'locations' => $this->locations->implode('name', ', '),
            'manDaysStatus' => StatusTypes::getCaseNameByValue($this->filterData['status']),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(40); // Ref #
        $sheet->getColumnDimension('B')->setWidth(30); // Status
        $sheet->getColumnDimension('C')->setWidth(50); // Date
        $sheet->getColumnDimension('D')->setWidth(15); // Total Man Days

        // Get the last row
        $lastRow = $sheet->getHighestRow();

        // Add borders to all cells
        $sheet->getStyle('A1:D'.$lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Style the column headers
        $headerRow = 8; // Adjust based on your actual header row
        $sheet->getStyle('A'.$headerRow.':D'.$headerRow)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ]);

        // Center align specific columns
        $sheet->getStyle('D1:D'.$lastRow)->getAlignment()->setHorizontal('center');

        // Set text wrapping for reference column
        $sheet->getStyle('A1:A'.$lastRow)->getAlignment()->setWrapText(true);

        return [];
    }
}
