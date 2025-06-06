<?php

declare(strict_types=1);

namespace App\Domains\StockTakeProduct\Exports;

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\StockTakeProduct;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreManagerStockTakeProductExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockTakeProducts
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockTakeProducts->map(function (StockTakeProduct $stockTakeProduct): array {
            /** @var Product $product */
            $product = $stockTakeProduct->product;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $product->unitOfMeasure;

            return [
                'product_name' => $product->name,
                'upc' => $product->upc,
                'ean' => $product->ean,
                'size' => $size instanceof Size ? $size->getName() : null,
                'color' => $color instanceof Color ? $color->getName() : null,
                'unit_of_measure' => $unitOfMeasure instanceof UnitOfMeasure ? $unitOfMeasure->name : null,
                'article_number' => $product->article_number,
                'submitted_stock' => $stockTakeProduct->submitted_stock,
            ];
        });
    }

    public function headings(): array
    {
        return ['product_name', 'upc', 'ean', 'size', 'color', 'unit_of_measure', 'Article Number', 'submitted_stock'];
    }
}
