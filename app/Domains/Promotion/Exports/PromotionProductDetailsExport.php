<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Exports;

use App\Domains\Product\Services\ProductService;
use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromotionProductDetailsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Promotion $promotions,
        protected int $type,
    ) {
    }

    public function collection(): Collection
    {
        $products = null;
        $productService = resolve(ProductService::class);
        /** @var Collection $products */
        if ($this->type === ProductUploadTypes::REGULAR->value) {
            $products = $this->promotions->regularProducts;
        }

        if ($this->type === ProductUploadTypes::BUY_PRODUCT->value) {
            $products = $this->promotions->buyProducts;
        }

        if ($this->type === ProductUploadTypes::GET_PRODUCT->value) {
            $products = $this->promotions->getProducts;
        }

        return $products->map(function (Product $product) use ($productService): array {
            $data = [
                'name' => $product->name,
                'upc' => $product->upc,
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
        $headings = ['Name', 'UPC'];

        if (config('app.product_variant')) {
            return array_merge($headings, ['Attributes']);
        }

        return array_merge($headings, ['Color', 'Size']);
    }
}
