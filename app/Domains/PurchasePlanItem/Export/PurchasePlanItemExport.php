<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlanItem\Export;

use App\Domains\Product\Services\ProductService;
use App\Models\Product;
use App\Models\PurchasePlanItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchasePlanItemExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $purchasePlanItems
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->purchasePlanItems->map(function (PurchasePlanItem $purchasePlanItem) use (
            $productService
        ): array {
            /** @var Product $product */
            $product = $purchasePlanItem->product;

            [$color, $size] = $productService->getColorAndSize($product);

            return [
                'id' => $purchasePlanItem->id,
                'product_name' => $product->name,
                'product_upc' => $product->upc,
                'product_color' => $color,
                'product_size' => $size,
                'quantity' => $purchasePlanItem->quantity,
                'transferred_quantity' => $purchasePlanItem->transferred_quantity ?? 0,
                'cost_price' => $purchasePlanItem->cost_price ?? 0,
                'total_price' => $purchasePlanItem->total_price ?? 0,
                'remarks' => $purchasePlanItem->remarks ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Id',
            'Product Name',
            'Product UPC',
            'Color',
            'Size',
            'Quantity',
            'Transferred Quantity',
            'Cost Price',
            'Total Price',
            'Remarks',
        ];
    }
}
