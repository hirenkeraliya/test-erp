<?php

declare(strict_types=1);

namespace App\Domains\State\Exports;

use App\Models\State;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StateExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(
        protected Collection $states
    ) {
    }

    public function collection(): Collection
    {
        return $this->states->map(fn (State $state): array => [
            'country' => $state->country?->name,
            'name' => $state->name,
            'country_code' => $state->country_code ?? 'N/A',
        ]);
    }

    public function headings(): array
    {
        return ['Country', 'Name', 'Country Code'];
    }
}
