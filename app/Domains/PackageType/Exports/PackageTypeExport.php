<?php

declare(strict_types=1);

namespace App\Domains\PackageType\Exports;

use App\Models\PackageType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PackageTypeExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $packageType
    ) {
    }

    public function collection(): Collection
    {
        return $this->packageType->map(fn (PackageType $packageType): array => [$packageType->name]);
    }

    public function headings(): array
    {
        return ['Name'];
    }
}
