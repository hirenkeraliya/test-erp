<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem\Export;

use App\Domains\Product\Services\ProductService;
use App\Models\Product;
use App\Models\PurchaseOrderFulfillmentItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrderFulfillmentItemExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $purchaseOrderFulfillmentItems
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->purchaseOrderFulfillmentItems->map(
            function (PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem) use ($productService): array {
                /** @var Product $product */
                $product = $purchaseOrderFulfillmentItem->product;

                $data = [
                    'id' => $purchaseOrderFulfillmentItem->id,
                    'product_name' => $product->name,
                    'product_upc' => $product->upc,
                    'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity ?? 0,
                    'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity ?? 0,
                    'remarks' => $purchaseOrderFulfillmentItem->remarks ?? 'N/A',
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
            }
        );
    }

    public function headings(): array
    {
        $headingColumns = [
            'Id',
            'Product Name',
            'Product UPC',
            'Transfer Quantity',
            'Received Quantity',
            'Remarks',
        ];

        if (config('app.product_variant')) {
            return array_merge($headingColumns, ['attributes']);
        }

        return array_merge($headingColumns, ['Color', 'Size']);
    }
}
