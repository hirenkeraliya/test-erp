<?php

declare(strict_types=1);

namespace App\Domains\Brand\Exports;

use App\Models\Brand;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $brands
    ) {
    }

    public function collection(): Collection
    {
        return $this->brands->map(fn (Brand $brand): array => [
            'name' => $brand->name,
            'code' => $brand->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
