<?php

declare(strict_types=1);

namespace App\Domains\SaleReturnReason\Exports;

use App\Models\SaleReturnReason;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleReturnReasonExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $saleReturnReasons
    ) {
    }

    public function collection(): Collection
    {
        return $this->saleReturnReasons->map(fn (SaleReturnReason $saleReturnReason): array => [
            'reason' => $saleReturnReason->reason,
            'put_back_in_inventory' => $saleReturnReason->put_back_in_inventory ? 'Yes' : 'No',
        ]);
    }

    public function headings(): array
    {
        return ['Reason', 'Put Back In Inventory'];
    }
}
