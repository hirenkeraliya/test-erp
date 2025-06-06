<?php

declare(strict_types=1);

namespace App\Domains\VoidSaleReason\Exports;

use App\Models\VoidSaleReason;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VoidSaleReasonExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $voidSaleReasons
    ) {
    }

    public function collection(): Collection
    {
        return $this->voidSaleReasons->map(fn (VoidSaleReason $voidSaleReason): array => [
            'reason' => $voidSaleReason->reason,
        ]);
    }

    public function headings(): array
    {
        return ['Reason'];
    }
}
