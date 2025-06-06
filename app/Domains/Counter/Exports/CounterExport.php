<?php

declare(strict_types=1);

namespace App\Domains\Counter\Exports;

use App\Models\Counter;
use App\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CounterExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $counters
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
        return $this->counters->map(function (Counter $counter): array {
            /** @var Location $location */
            $location = $counter->location;

            return [
                'name' => $counter->name,
                'location' => $location->name,
                'is_locked' => $counter->is_locked ? 'Yes' : 'No',
                'is_self_checkout' => $counter->is_self_checkout ? 'Yes' : 'No',
            ];
        });
    }

    public function headings(): array
    {
        return ['name', 'location', 'is_locked', 'is_self_checkout'];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1', 'C1', 'D1'];
    }
}
