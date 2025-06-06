<?php

declare(strict_types=1);

namespace App\Domains\Category\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $categories,
        protected array $columns,
    ) {
    }

    public function collection(): Collection
    {
        return $this->categories;
    }

    public function headings(): array
    {
        return ['Category', ...$this->columns];
    }
}
