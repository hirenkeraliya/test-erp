<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesByPromoterExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $promoters,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->promoters->map(function (array $promoter): array {
            $salesByPromoterData = [
                'promoter' => $promoter['promoter'],
                'locations' => $promoter['locations'],
                'units_sold' => $promoter['units_sold'],
                'units_returned' => $promoter['units_returned'],
                'per_sales_with_staff_help' => $promoter['per_sales_with_staff_help'],
                'units_per_transaction' => $promoter['units_per_transaction'],
                'average_transaction_value' => $promoter['average_transaction_value'],
                'return_amount' => CommonFunctions::currencyFormat((float) $promoter['return_amount']),
                'gross_amount' => CommonFunctions::currencyFormat((float) $promoter['gross_amount']),
                'discount_amount' => CommonFunctions::currencyFormat((float) $promoter['discount_amount']),
                'tax_amount' => CommonFunctions::currencyFormat((float) $promoter['tax_amount']),
                'net_amount' => CommonFunctions::currencyFormat((float) $promoter['net_amount']),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($salesByPromoterData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
