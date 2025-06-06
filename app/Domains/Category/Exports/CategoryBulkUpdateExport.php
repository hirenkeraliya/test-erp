<?php

declare(strict_types=1);

namespace App\Domains\Category\Exports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryBulkUpdateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $categories
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
        return $this->categories->map(fn (Category $category): array => [
            'name' => $category->name,
            'code' => $category->code,
            'description' => $category->description,
            'status' => $category->status ? 'Yes' : 'No',
            'is_available_in_ecommerce' => $category->is_available_in_ecommerce ? 'Yes' : 'No',
            'is_display_on_menu' => $category->is_display_on_menu ? 'Yes' : 'No',
        ]);
    }

    public function headings(): array
    {
        return ['name', 'code', 'description', 'status', 'is_available_in_ecommerce', 'is_display_on_menu'];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1'];
    }
}
