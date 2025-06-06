<?php

declare(strict_types=1);

namespace App\Domains\SaleReturn\Exports;

use App\Domains\Common\Services\ExportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DifferentStoreReturnsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $saleReturns,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $differentStoreReturnData = $this->saleReturns->transform(fn ($saleReturn): array => [
            'digital_invoice_number' => $saleReturn['digital_invoice_number'],
            'offline_sale_return_id' => $saleReturn['offline_sale_return_id'],
            'original_receipt_id' => $saleReturn['original_receipt_id'],
            'original_location' => $saleReturn['original_location']['name'],
            'return_location' => $saleReturn['return_location']['name'],
            'sale_counter' => $saleReturn['sale_counter'],
            'sale_return_counter' => $saleReturn['sale_return_counter'],
            'sale_cashier' => $saleReturn['sale_cashier'],
            'sale_return_cashier' => $saleReturn['sale_return_cashier'],
            'sale_happened_at' => $saleReturn['sale_happened_at'],
            'sale_return_happened_at' => $saleReturn['sale_return_happened_at'],
            'member' => $saleReturn['member'],
            'sale_return_amount' => $saleReturn['sale_return_amount'],
        ]);

        $exportService = resolve(ExportService::class);

        return $exportService->exportDataMapping($differentStoreReturnData, $this->filteredColumns);
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
