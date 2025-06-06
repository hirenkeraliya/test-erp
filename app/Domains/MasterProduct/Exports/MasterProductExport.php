<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\Enums\BulkProductUpdateImportColumns;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\UnitOfMeasure;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color as StyleColor;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterProductExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $masterProducts,
        protected Collection $filteredColumns,
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
        return $this->masterProducts->map(function (MasterProduct $masterProduct): array {
            /** @var Brand $brand */
            $brand = $masterProduct->brand;

            /** @var ?Vendor $vendor */
            $vendor = $masterProduct->vendor;

            /** @var ?Department $department */
            $department = $masterProduct->department;

            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $masterProduct->unitOfMeasure;

            /** @var Category $category */
            $category = $masterProduct->categories;

            /** @var Carbon $createdAt */
            $createdAt = $masterProduct->created_at;

            /** @var Carbon $updatedAt */
            $updatedAt = $masterProduct->updated_at;

            [$category, $parentSubcategory, $subSubcategories] = $this->getProductCategories(
                $masterProduct->categories
            );

            $optionalColumns = $this->getOptionalPermissionColumns();

            $masterProductDetails = [
                'name' => $masterProduct->name,
                'code' => $masterProduct->code,
                'brand' => $brand->name,
                'categories' => $category ? $category->name : 'N/A',
                'article_number' => $masterProduct->article_number,
                'original_created_at' => $masterProduct->original_created_at,
                'description' => $masterProduct->description,
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
                'unit_of_measure' => $unitOfMeasure instanceof UnitOfMeasure ? $unitOfMeasure->getName() : null,
                'department' => $department instanceof Department ? $department->name : null,
                'type_id' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                'subcategory_name' => $parentSubcategory ? $parentSubcategory->name : null,
                'subsubcategory_name' => $subSubcategories->isNotEmpty() ? $subSubcategories->implode(
                    'name',
                    ' > '
                ) : null,
                'has_batch' => $masterProduct->has_batch ? 'Yes' : 'No',
                'is_non_inventory' => $masterProduct->is_non_inventory ? 'Yes' : 'No',
                'is_non_selling_item' => $masterProduct->is_non_selling_item ? 'Yes' : 'No',
                'status' => $this->getStatus($masterProduct->status),
                'vendor' => $vendor instanceof Vendor ? $vendor->name : null,
            ];

            $data = array_diff_key($masterProductDetails, array_flip($optionalColumns));

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($data, $this->filteredColumns);
        });
    }

    private function getStatus(int $status): string
    {
        if ($status === Statuses::ACTIVE->value) {
            return Statuses::getCaseName(Statuses::ACTIVE->value);
        }

        if ($status === Statuses::ARCHIVED->value) {
            return Statuses::getCaseName(Statuses::ARCHIVED->value);
        }

        return '';
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

    /**
     * @return mixed[]
     */
    private function getProductCategories(Collection $categories): array
    {
        $category = $categories->first();
        $parentSubcategory = $categories->firstWhere('pivot.sort_order', 1);
        $subSubcategories = $categories->where('pivot.sort_order', '>=', 2);

        return [$category, $parentSubcategory, $subSubcategories];
    }

    private function getOptionalPermissionColumns(): array
    {
        $assignedPermissions = array_map(
            fn ($value): string => 'item_' . $value,
            PermissionModuleService::getModuleSubLists()['Item'] ?? []
        );

        return array_map(
            fn ($value): string => Str::replace('item_', '', $value),
            array_diff($assignedPermissions, $this->allPermissionLists)
        );
    }
}
