<?php

declare(strict_types=1);

namespace App\Domains\TransitStock\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Product\Services\ProductService;
use App\Domains\TransitStock\Services\TransitInventoryReportService;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\TransitStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransitInventoryExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $transitInventories,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $transitInventoryReportService = resolve(TransitInventoryReportService::class);
        $productService = resolve(ProductService::class);

        return $this->transitInventories->map(function (TransitStock $transitStock) use (
            $transitInventoryReportService,
            $productService
        ): array {
            $referenceNumberArray = $transitInventoryReportService->getTransitInventoryReportReferenceNumber(
                $transitStock,
                'admin'
            );
            /** @var Inventory $inventory */
            $inventory = $transitStock->inventory;
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

            $transitInventoryData = [
                'item_name' => $product->name,
                'article_number' => $product->article_number,
                ...$colorSizeOrAttributeData,
                'upc' => $product->upc,
                'reference' => $referenceNumberArray['message'],
                'stock' => CommonFunctions::numberFormatString((float) $transitStock->quantity),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($transitInventoryData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
