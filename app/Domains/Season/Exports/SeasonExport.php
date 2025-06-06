<?php

declare(strict_types=1);

namespace App\Domains\Season\Exports;

use App\Models\Season;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SeasonExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $seasons
    ) {
    }

    public function collection(): Collection
    {
        return $this->seasons->map(fn (Season $season): array => [
            'name' => $season->name,
            'code' => $season->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
