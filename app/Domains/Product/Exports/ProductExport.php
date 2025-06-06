<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\Enums\BulkProductUpdateImportColumns;
use App\Domains\Product\Services\ProductService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color as StyleColor;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $products,
        protected Collection $filteredColumns,
        protected array $allPermissionLists = [],
    ) {
    }

    public function styles(Worksheet $sheet): void
    {
        $headerCellNumbers = collect([]);
        $headerColumnsThatAreRequired = BulkProductUpdateImportColumns::getRequiredColumns();

        $headerColumnCoordinates = collect($sheet->getCoordinates())->filter(
            fn ($item): bool => str_ends_with($item, '1')
        )->values()->all();

        foreach ($headerColumnCoordinates as $headerColumnCoordinate) {
            $columnName = (string) $sheet->getCellCollection()->get($headerColumnCoordinate)?->getValue();
            if (in_array($columnName, $headerColumnsThatAreRequired, true)) {
                $headerCellNumbers->push($headerColumnCoordinate);
            }
        }

        foreach ($headerCellNumbers as $headerCellNumber) {
            $sheet->getStyle($headerCellNumber)
                ->getFont()
                ->getColor()
                ->setARGB(StyleColor::COLOR_RED);
        }
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $productService->preparedProductRecords(
            $this->products,
            $this->filteredColumns,
            $this->allPermissionLists,
        );
    }

    public function headings(): array
    {
        // Note: When adding a new column at this location, ensure that you also make the necessary updates to the BulkProductUpdateImportColumns enum. This enum is responsible for validating the columns in the import record.

        $optionalColumns = $this->getOptionalPermissionColumns();

        $productColumns = $this->filteredColumns
            ->reject(fn ($column): bool => in_array($column, ['images', 'thumbnail_url', 'action']))
            ->map(fn ($column) => Str::title(str_replace('_', ' ', $column)))->toArray();

        return array_diff($productColumns, $optionalColumns);
    }

    private function getOptionalPermissionColumns(): array
    {
        $assignedPermissions = array_map(
            fn ($value): string => 'product_' . $value,
            PermissionModuleService::getModuleSubLists()['Product']
        );

        return array_map(
            fn ($value): string => Str::replace('product_', '', $value),
            array_diff($assignedPermissions, $this->allPermissionLists)
        );
    }
}
