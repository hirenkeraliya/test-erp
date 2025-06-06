<?php

declare(strict_types=1);

namespace App\Domains\ReservedStock\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\Product\Services\ProductService;
use App\Domains\ReservedStock\Services\ReservedInventoryReportService;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ReservedStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReservedInventoryExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $reservedInventories,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $reservedInventoryReportService = resolve(ReservedInventoryReportService::class);
        $productService = resolve(ProductService::class);

        return $this->reservedInventories->map(function (ReservedStock $reservedStock) use (
            $reservedInventoryReportService,
            $productService
        ): array {
            $referenceNumberArray = $reservedInventoryReportService->getReservedInventoryReportReferenceNumber(
                $reservedStock,
                'admin'
            );

            /** @var Inventory $inventory */
            $inventory = $reservedStock->inventory;

            /** @var Product $product */
            $product = $inventory->product;

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            $reservedInventoryData = [
                'item_name' => $product->name,
                'article_number' => $product->article_number,
                ...$colorSizeOrAttributeData,
                'upc' => $product->upc,
                'reference' => $referenceNumberArray['message'],
                'stock' => $reservedStock->quantity,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($reservedInventoryData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
