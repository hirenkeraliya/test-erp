<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SellThroughBalanceExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $sellThroughBalanceData,
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->sellThroughBalanceData)->map(fn ($sellThroughBalanceData): array => [
            'location_name' => $sellThroughBalanceData['location_name'],
            'balance' => $sellThroughBalanceData['balance'],
        ]);
    }

    public function headings(): array
    {
        return ['Location Name', 'Balance'];
    }
}
