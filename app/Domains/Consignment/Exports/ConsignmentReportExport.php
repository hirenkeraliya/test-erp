<?php

declare(strict_types=1);

namespace App\Domains\Consignment\Exports;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Product\Services\ProductService;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsignmentReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $products,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->products->map(function ($product) use ($productService): array {
            /** @var Collection $saleItems */
            $saleItems = $product->saleItems;

            $unitSold = $saleItems->sum('quantity');

            $total = $unitSold * $product->retail_price;

            /** @var Vendor|null $vendor */
            $vendor = config('app.product_variant') ? $product->masterProduct?->vendor : $product->vendor;

            $commission = $vendor ? ($total * $vendor->commission_percentage) / 100 : 0;

            /** @var array $categories = [] */
            $categories = config('app.product_variant') ? $product->masterProduct?->categories->pluck(
                'name'
            )->toArray() ?? [] : $product->categories->pluck('name')->toArray();

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            $consignmentReportData = [
                'product' => $product->name,
                'upc' => $product->upc,
                'article_number' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
                'vendor' => $vendor ? $vendor->name : 'N/A',
                'categories' => implode(', ', $categories),
                'brand' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->brand?->name : $product->brand?->name,
                ...$colorSizeOrAttributeData,
                'unit_sold' => $unitSold,
                'price' => CommonFunctions::numberFormatString((float) $product->retail_price),
                'total' => CommonFunctions::numberFormatString((float) $total),
                'commission' => CommonFunctions::numberFormatString((float) $commission),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($consignmentReportData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
