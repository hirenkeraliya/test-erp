<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Exports;

use App\Domains\Product\Services\ProductService;
use App\Models\MysteryGift;
use App\Models\MysteryGiftProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MysteryGiftProductDetailsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected MysteryGift $mysteryGift,
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);
        /** @var Collection $mysteryGiftProducts */
        $mysteryGiftProducts = $this->mysteryGift->mysteryGiftProducts;

        return $mysteryGiftProducts->map(function (MysteryGiftProduct $mysteryGiftProduct) use (
            $productService
        ): array {
            $product = $mysteryGiftProduct->product;
            $data = [
                'name' => $product?->name,
                'upc' => $product?->upc,
                'quantity' => $mysteryGiftProduct->quantity,
            ];

            if (config('app.product_variant') && $product) {
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
        $headings = ['Name', 'UPC', 'Quantity'];

        if (config('app.product_variant')) {
            return array_merge($headings, ['Attributes']);
        }

        return array_merge($headings, ['Color', 'Size']);
    }
}
