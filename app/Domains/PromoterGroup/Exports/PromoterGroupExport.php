<?php

declare(strict_types=1);

namespace App\Domains\PromoterGroup\Exports;

use App\Models\PromoterGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromoterGroupExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $promoterGroups
    ) {
    }

    public function collection(): Collection
    {
        return $this->promoterGroups->map(fn (PromoterGroup $promoterGroup): array => [
            'name' => $promoterGroup->name,
            'code' => $promoterGroup->code,
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Code'];
    }
}
