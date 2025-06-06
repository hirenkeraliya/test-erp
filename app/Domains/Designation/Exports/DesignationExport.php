<?php

declare(strict_types=1);

namespace App\Domains\Designation\Exports;

use App\Models\Designation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DesignationExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $designations
    ) {
    }

    public function collection(): Collection
    {
        return $this->designations->map(fn (Designation $designation): array => [
            'name' => $designation->name,
            'code' => $designation->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
