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

class BulkUpdateProductExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $products,
        protected array $allPermissionLists = []
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

        return $productService->preparedProductRecordsForBulkUpdate($this->products, $this->allPermissionLists);
    }

    public function headings(): array
    {
        // Note: When adding a new column at this location, ensure that you also make the necessary updates to the BulkProductUpdateImportColumns enum. This enum is responsible for validating the columns in the import record.

        $optionalColumns = $this->getOptionalPermissionColumns();

        $productColumns = [
            'name',
            'description',
            'code',
            'unit_of_measure',
            'season',
            'department',
            'sub_department',
            'color',
            'size',
            'style',
            'is_temporarily_unavailable',
            'upc',
            'verification_qr_code',
            'ean',
            'custom_sku',
            'manufacturer_sku',
            'brand',
            'type_id',
            'retail_price',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'purchase_cost',
            'online_price',
            'article_number',
            'category_name',
            'subcategory_name',
            'subsubcategory_name',
            'has_batch',
            'is_non_inventory',
            'is_non_selling_item',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_sold_as_single_item',
            'sell_item_via_derivative',
            'status',
            'tags',
            'vendor',
            'sale_channels',
            'original_created_at',
            'created_at',
        ];

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
