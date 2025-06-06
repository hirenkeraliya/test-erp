<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderPartialReceiveItem\Export;

use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExternalPurchaseOrderPartialReceiveItemExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $externalPurchaseOrderPartialReceiveItems
    ) {
    }

    public function collection(): Collection
    {
        return $this->externalPurchaseOrderPartialReceiveItems->map(
            function (ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem): array {
                /** @var ExternalPurchaseOrderItem $externalPurchaseOrderItem */
                $externalPurchaseOrderItem = $externalPurchaseOrderPartialReceiveItem->externalPurchaseOrderItem;

                /** @var Product $product */
                $product = $externalPurchaseOrderItem->product;

                return [
                    'id' => $externalPurchaseOrderPartialReceiveItem->id,
                    'product_name' => $product->name,
                    'product_upc' => $product->upc,
                    'product_color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
                    'product_size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
                    'quantity' => $externalPurchaseOrderItem->quantity ?? 0,
                    'received_quantity' => $externalPurchaseOrderPartialReceiveItem->quantity_received ?? 0,
                    'notes' => $externalPurchaseOrderPartialReceiveItem->notes ?? 'N/A',
                ];
            }
        );
    }

    public function headings(): array
    {
        return ['Id', 'Product Name', 'Product UPC', 'Color', 'Size', 'Quantity', 'Received Quantity', 'Notes'];
    }
}
