<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Exports;

use App\Models\PaymentType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentTypeBulkUpdateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $paymentTypes
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
        return $this->paymentTypes->map(fn (PaymentType $paymentType): array => [
            'name' => $paymentType->name,
            'is_member_required' => $paymentType->is_member_required ? 'Yes' : 'No',
            'is_available_for_refund' => $paymentType->is_available_for_refund ? 'Yes' : 'No',
            'payment_terminal_key' => $paymentType->payment_terminal_key,
        ]);
    }

    public function headings(): array
    {
        return ['name', 'is_member_required', 'is_available_for_refund', 'payment_terminal_key'];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1', 'C1'];
    }
}
