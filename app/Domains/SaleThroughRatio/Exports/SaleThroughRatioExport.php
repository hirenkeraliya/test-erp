<?php

declare(strict_types=1);

namespace App\Domains\SaleThroughRatio\Exports;

use App\Models\SaleThroughRatio;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleThroughRatioExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $saleThroughRatios
    ) {
    }

    public function collection(): Collection
    {
        return $this->saleThroughRatios->map(fn (SaleThroughRatio $saleThroughRatio): array => [
            'name' => $saleThroughRatio->name,
            'percentage' => $saleThroughRatio->percentage,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Percentage'];
    }
}
