<?php

declare(strict_types=1);

namespace App\Domains\Style\Exports;

use App\Models\Style;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StyleExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $styles
    ) {
    }

    public function collection(): Collection
    {
        return $this->styles->map(fn (Style $style): array => [
            'name' => $style->name,
            'code' => $style->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
