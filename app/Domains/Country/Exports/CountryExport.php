<?php

declare(strict_types=1);

namespace App\Domains\Country\Exports;

use App\Models\Country;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CountryExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $countries
    ) {
    }

    public function collection(): Collection
    {
        return $this->countries->map(fn (Country $country): array => [
            'iso2' => $country->iso2,
            'name' => $country->name,
            'phone_code' => $country->phone_code,
            'iso3' => $country->iso3,
            'region' => $country->region,
            'subregion' => $country->subregion,
        ]);
    }

    public function headings(): array
    {
        return ['Iso2', 'Name', 'Phone Code', 'Iso3', 'Region', 'Subregion'];
    }
}
