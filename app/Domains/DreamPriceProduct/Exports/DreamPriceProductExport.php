<?php

declare(strict_types=1);

namespace App\Domains\DreamPriceProduct\Exports;

use App\Domains\Product\Services\ProductService;
use App\Models\DreamPriceProduct;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DreamPriceProductExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $dreamPriceProducts
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->dreamPriceProducts->map(function (DreamPriceProduct $dreamPriceProduct) use (
            $productService
        ): array {
            /** @var Product $product */
            $product = $dreamPriceProduct->product;

            $data = [
                'product_name' => $product->name,
                'upc' => $product->upc,
                'price' => $product->retail_price,
                'promo_price' => $dreamPriceProduct->price,
            ];

            if (config('app.product_variant')) {
                $data['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $data['color'] = $product->color?->name ?? 'N/A';
                $data['size'] = $product->size?->name ?? 'N/A';
            }

            return $data;
        });
    }

    public function headings(): array
    {
        $headings = ['Product Name', 'Upc', 'Price', 'Promo Price'];

        if (config('app.product_variant')) {
            return array_merge($headings, ['Attributes']);
        }

        return array_merge($headings, ['Color', 'Size']);
    }
}
