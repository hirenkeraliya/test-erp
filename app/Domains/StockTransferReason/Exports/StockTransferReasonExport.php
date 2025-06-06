<?php

declare(strict_types=1);

namespace App\Domains\StockTransferReason\Exports;

use App\Models\StockTransferReason;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockTransferReasonExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $stockTransferReasons
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockTransferReasons->map(fn (StockTransferReason $stockTransferReason): array => [
            'name' => $stockTransferReason->name,
            'code' => $stockTransferReason->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
