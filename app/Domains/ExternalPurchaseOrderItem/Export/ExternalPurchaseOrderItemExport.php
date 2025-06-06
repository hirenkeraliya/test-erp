<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem\Export;

use App\Models\ExternalPurchaseOrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExternalPurchaseOrderItemExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $externalPurchaseOrderItems
    ) {
    }

    public function collection(): Collection
    {
        return $this->externalPurchaseOrderItems->map(
            function (ExternalPurchaseOrderItem $externalPurchaseOrderItem): array {
                /** @var Product $product */
                $product = $externalPurchaseOrderItem->product;

                return [
                    'id' => $externalPurchaseOrderItem->id,
                    'product_name' => $product->name,
                    'product_upc' => $product->upc,
                    'quantity' => $externalPurchaseOrderItem->quantity ?? 0,
                    'received_quantity' => $externalPurchaseOrderItem->received_quantity ?? 0,
                    'remarks' => $externalPurchaseOrderItem->remarks ?? 'N/A',
                    'product_color' => $product->color?->name ?? 'N/A',
                    'product_size' => $product->size?->name ?? 'N/A',
                ];
            }
        );
    }

    public function headings(): array
    {
        return ['Id', 'Product Name', 'Product UPC', 'Quantity', 'Received Quantity', 'Remarks', 'Color', 'Size'];
    }
}
