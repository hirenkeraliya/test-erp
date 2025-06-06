<?php

declare(strict_types=1);

namespace App\Domains\Denomination\Exports;

use App\Models\Denomination;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DenominationExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $denominations
    ) {
    }

    public function collection(): Collection
    {
        return $this->denominations->map(fn (Denomination $denomination): array => [
            'denomination' => $denomination->denomination,
        ]);
    }

    public function headings(): array
    {
        return ['Denomination'];
    }
}
