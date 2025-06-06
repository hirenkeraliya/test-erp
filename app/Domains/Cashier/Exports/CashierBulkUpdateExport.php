<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Exports;

use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashierBulkUpdateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $cashiers
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
        return $this->cashiers->map(function (Cashier $cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;
            /** @var CashierGroup $cashierGroup */
            $cashierGroup = $cashier->cashierGroup;
            /** @var Collection $locations */
            $locations = $cashier->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'mobile_number' => $employee->mobile_number,
                'username' => $cashier->username,
                'cashier_group ' => $cashierGroup->name,
                'locations' => $locationNames,
            ];
        });
    }

    public function headings(): array
    {
        return ['first_name', 'mobile_number', 'username', 'cashier_group', 'locations'];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1', 'C1', 'D1', 'E1'];
    }
}
