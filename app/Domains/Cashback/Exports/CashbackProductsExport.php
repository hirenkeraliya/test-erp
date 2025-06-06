<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Exports;

use App\Domains\Product\Services\ProductService;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashbackProductsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $cashbackProducts
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->cashbackProducts->map(function (Product $product) use ($productService): array {
            $data = [
                'name' => $product->name,
                'upc' => $product->upc,
            ];

            if (config('app.product_variant')) {
                $data['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $data['color_name'] = $product?->color?->name ?? 'N/A';
                $data['size_name'] = $product?->size?->name ?? 'N/A';
            }

            return $data;
        });
    }

    public function headings(): array
    {
        $headings = ['Name', 'UPC'];

        if (config('app.product_variant')) {
            $headings[] = 'Attributes';
        } else {
            $headings[] = 'Color';
            $headings[] = 'Size';
        }

        return $headings;
    }
}
