<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Exports;

use App\Models\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $vendors
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
        return $this->vendors->map(fn (Vendor $vendor): array => [
            'name' => $vendor->name,
            'code' => $vendor->code,
            'phone' => $vendor->phone,
            'mobile' => $vendor->mobile,
            'email' => $vendor->email,
            'sst_number' => $vendor->sst_number,
            'registration_number' => $vendor->registration_number,
            'address_line_1' => $vendor->address_line_1,
            'address_line_2' => $vendor->address_line_2,
            'city' => $vendor->city,
            'area_code' => $vendor->area_code,
            'fax' => $vendor->fax,
            'website' => $vendor->website,
            'consignment' => $vendor->is_consignment ? 'Yes' : 'No',
            'commission_percentage' => $vendor->commission_percentage,
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'code',
            'phone',
            'mobile',
            'email',
            'sst_number',
            'registration_number',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'fax',
            'website',
            'consignment',
            'commission_percentage',
        ];
    }

    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'C1', 'E1', 'H1', 'J1', 'K1', 'J1'];
    }
}
