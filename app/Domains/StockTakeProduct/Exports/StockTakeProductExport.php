<?php

declare(strict_types=1);

namespace App\Domains\StockTakeProduct\Exports;

use App\CommonFunctions;
use App\Domains\Product\Services\ProductService;
use App\Models\Product;
use App\Models\StockTakeProduct;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockTakeProductExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockTakeProducts
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->stockTakeProducts->map(function (StockTakeProduct $stockTakeProduct) use (
            $productService
        ): array {
            /** @var Product $product */
            $product = $stockTakeProduct->product;

            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = config(
                'app.product_variant'
            ) ? $product->masterProduct?->unitOfMeasure : $product->unitOfMeasure;

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            return [
                'product_name' => $product->name,
                'upc' => $product->upc,
                'ean' => $product->ean,
                'article_number' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
                ...$colorSizeOrAttributeData,
                'unit_of_measure' => $unitOfMeasure instanceof UnitOfMeasure ? $unitOfMeasure->name : null,
                'actual_stock' => $stockTakeProduct->actual_stock ?? 0,
                'submitted_stock' => $stockTakeProduct->submitted_stock,
                'variation_stock' => CommonFunctions::numberFormatString(
                    $stockTakeProduct->submitted_stock - $stockTakeProduct->actual_stock
                ),
            ];
        });
    }

    public function headings(): array
    {
        $headerData = config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'];

        return [
            'Product Name',
            'UPC',
            'EAN',
            'Article Number',
            ...$headerData,
            'Unit Of Measure',
            'Actual Stock',
            'Submitted Stock',
            'Variation Stock',
        ];
    }
}
