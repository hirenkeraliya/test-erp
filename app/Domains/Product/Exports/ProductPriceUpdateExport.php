<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\Enums\BulkProductPriceUpdateImportColumns;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductPriceUpdateExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $allPermissionLists = []
    ) {
    }

    public function collection(): Collection
    {
        $columns = collect(BulkProductPriceUpdateImportColumns::cases())
            ->map(fn ($column): array => [
                $column->value => 'upc' === $column->value ? 'ABCXYZ123' : 123,
            ])
            ->collapse()
            ->toArray();

        $optionalColumns = $this->getOptionalPermissionColumns();

        return collect([array_diff_key($columns, array_flip($optionalColumns))]);
    }

    public function headings(): array
    {
        // Note: If you want to add a column here, kindly update the BulkProductPriceUpdateImportColumns enum, as it is used to validate and authorize columns.

        $optionalColumns = $this->getOptionalPermissionColumns();

        if ([] !== $optionalColumns) {
            return collect(BulkProductPriceUpdateImportColumns::cases())
                ->whereNotIn('value', $optionalColumns)
                ->pluck('value')
                ->toArray();
        }

        return collect(BulkProductPriceUpdateImportColumns::cases())->pluck('value')->toArray();
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
