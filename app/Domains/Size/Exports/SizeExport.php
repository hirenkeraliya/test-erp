<?php

declare(strict_types=1);

namespace App\Domains\Size\Exports;

use App\Models\Size;
use App\Models\SizeGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SizeExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $sizes
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
        return $this->sizes->map(function (Size $size): array {
            /** @var ?SizeGroup $sizeGroup */
            $sizeGroup = $size->sizeGroup;

            /** @var ?Size $sortingSize */
            $sortingSize = $size->sortingSize;

            return [
                'name' => $size->name,
                'code' => $size->code,
                'size_group' => $sizeGroup?->name,
                'create_after' => $sortingSize?->name,
            ];
        });
    }

    public function headings(): array
    {
        return ['name', 'code', 'size_group', 'create_after'];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'D1'];
    }
}
