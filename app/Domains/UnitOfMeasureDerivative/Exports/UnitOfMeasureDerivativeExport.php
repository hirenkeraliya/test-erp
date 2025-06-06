<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasureDerivative\Exports;

use App\Models\UnitOfMeasureDerivative;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnitOfMeasureDerivativeExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $unitOfMeasureDerivatives
    ) {
    }

    public function collection(): Collection
    {
        return $this->unitOfMeasureDerivatives->map(fn (UnitOfMeasureDerivative $unitOfMeasureDerivative): array => [
            'name' => $unitOfMeasureDerivative->name,
            'ratio' => $unitOfMeasureDerivative->ratio,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Ratio'];
    }
}
