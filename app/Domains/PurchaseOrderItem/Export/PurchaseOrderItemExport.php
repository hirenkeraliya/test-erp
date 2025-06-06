<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderItem\Export;

use App\Domains\Product\Services\ProductService;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrderItemExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $purchaseOrderItems
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->purchaseOrderItems->map(function (PurchaseOrderItem $purchaseOrderItem) use (
            $productService
        ): array {
            /** @var Product $product */
            $product = $purchaseOrderItem->product;

            $data = [
                'id' => $purchaseOrderItem->id,
                'product_name' => $product->name,
                'product_upc' => $product->upc,
                'quantity' => $purchaseOrderItem->quantity,
                'rejected_quantity' => $purchaseOrderItem->rejected_quantity ?? 0,
                'transferred_quantity' => $purchaseOrderItem->transferred_quantity ?? 0,
                'price_per_unit' => $purchaseOrderItem->price_per_unit ?? 0,
                'remarks' => $purchaseOrderItem->remarks ?? 'N/A',
            ];

            if (config('app.product_variant')) {
                return array_merge($data, [
                    'attributes' => $productService->getAttributesForPrint($product),
                ]);
            }

            return array_merge($data, [
                'product_color' => $product->color?->name ?? 'N/A',
                'product_size' => $product->size?->name ?? 'N/A',
            ]);
        });
    }

    public function headings(): array
    {
        $headingColumns = [
            'Id',
            'Product Name',
            'Product UPC',
            'Quantity',
            'Rejected Quantity',
            'Transferred Quantity',
            'Price Per Unit',
            'Remarks',
        ];

        if (config('app.product_variant')) {
            return array_merge($headingColumns, ['attributes']);
        }

        return array_merge($headingColumns, ['Color', 'Size']);
    }
}
