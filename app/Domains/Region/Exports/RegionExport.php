<?php

declare(strict_types=1);

namespace App\Domains\Region\Exports;

use App\Models\Region;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegionExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $regions
    ) {
    }

    public function styles(Worksheet $sheet): void
    {
        $headerCellNumbers = $this->getRequiredFieldsHeaderCellNumbers();

        foreach ($headerCellNumbers as $headerCellNumber) {
            $sheet->getStyle($headerCellNumber)
                ->getFont()
                ->getColor()
                ->setARGB(Color::COLOR_RED);
        }
    }

    public function collection(): Collection
    {
        return $this->regions->map(fn (Region $region): array => [
            'name' => $region->name,
            'code' => $region->code,
            'manager_name' => $region->manager_name,
            'manager_email' => $region->manager_email,
        ]);
    }

    public function headings(): array
    {
        return ['name', 'code', 'manager_name', 'manager_email'];
    }

    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1'];
    }
}
