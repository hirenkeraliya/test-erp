<?php

declare(strict_types=1);

namespace App\Domains\City\Exports;

use App\Models\City;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CityExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(
        protected Collection $cities
    ) {
    }

    public function collection(): Collection
    {
        return $this->cities->map(fn (City $city): array => [
            'country' => $city->country?->name,
            'state' => $city->state?->name,
            'name' => $city->name,
            'country_code' => $city->country_code ?? 'N/A',
        ]);
    }

    public function headings(): array
    {
        return ['Country', 'State', 'Name', 'Country Code'];
    }
}
