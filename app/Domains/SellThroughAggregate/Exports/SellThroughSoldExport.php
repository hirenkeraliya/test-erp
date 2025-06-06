<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SellThroughSoldExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $sellThroughSoldData,
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->sellThroughSoldData)->map(fn ($sellThroughSoldData): array => [
            'location_name' => $sellThroughSoldData['location_name'],
            'sold' => $sellThroughSoldData['sold'],
            'foc_sold' => $sellThroughSoldData['foc_sold'],
            'return' => $sellThroughSoldData['return'],
        ]);
    }

    public function headings(): array
    {
        return ['Location Name', 'Sold', 'Foc Sold', 'Return'];
    }
}
