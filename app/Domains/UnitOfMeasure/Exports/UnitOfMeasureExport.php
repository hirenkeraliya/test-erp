<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasure\Exports;

use App\Models\UnitOfMeasure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnitOfMeasureExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $unitOfMeasures
    ) {
    }

    public function collection(): Collection
    {
        return $this->unitOfMeasures->map(fn (UnitOfMeasure $unitOfMeasure): array => [
            'name' => $unitOfMeasure->name,
        ]);
    }

    public function headings(): array
    {
        return ['Name'];
    }
}
