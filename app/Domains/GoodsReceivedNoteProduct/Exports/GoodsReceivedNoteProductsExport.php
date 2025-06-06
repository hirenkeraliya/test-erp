<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNoteProduct\Exports;

use App\Domains\Product\Services\ProductService;
use App\Models\Batch;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Product;
use App\Models\PurchaseAmount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GoodsReceivedNoteProductsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $goodsReceivedNoteProducts
    ) {
    }

    public function collection(): Collection
    {
        $productService = resolve(ProductService::class);

        return $this->goodsReceivedNoteProducts->map(
            function (GoodsReceivedNoteProduct $goodsReceivedNoteProduct) use ($productService): array {
                /** @var Product $product */
                $product = $goodsReceivedNoteProduct->product;

                /** @var PurchaseAmount $purchaseAmount */
                $purchaseAmount = $goodsReceivedNoteProduct->purchaseAmount;

                /** @var ?Batch $batch */
                $batch = $goodsReceivedNoteProduct->batch;
                $batchExpiryDate = 'N/A';

                if ($batch && $batch->expiry_date) {
                    /** @var Carbon $batchExpiryDate */
                    $batchExpiryDate = Carbon::createFromFormat('Y-m-d', $batch->expiry_date);
                    $batchExpiryDate = $batchExpiryDate->format('d-m-Y');
                }

                $data = [
                    'product_upc' => $product->upc,
                    'product_name' => $product->name,
                    'article_number' => $this->getArticleNumber($product),
                    'quantity' => $goodsReceivedNoteProduct->quantity,
                    'fob' => $purchaseAmount->fob ?? 0,
                    'freight_charges' => $purchaseAmount->freight_charges ?? 0,
                    'insurance_charges' => $purchaseAmount->insurance_charges ?? 0,
                    'duty' => $purchaseAmount->duty ?? 0,
                    'sst' => $purchaseAmount->sst ?? 0,
                    'handling_charges' => $purchaseAmount->handling_charges ?? 0,
                    'other_charges' => $purchaseAmount->other_charges ?? 0,
                    'landed_cost' => $purchaseAmount->landed_cost,
                    'expiry_date' => $batchExpiryDate,
                ];

                if (config('app.product_variant')) {
                    return array_merge($data, [
                        'attributes' => $productService->getAttributesForPrint($product),
                    ]);
                }

                return array_merge($data, [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ]);
            }
        );
    }

    public function headings(): array
    {
        $headingColumns = [
            'Product UPC',
            'Product Name',
            'Article Number',
            'Quantity',
            'FOB',
            'Freight Charges',
            'Insurance Charges',
            'Duty',
            'SST',
            'Handling Charges',
            'Other Charges',
            'Landed Cost',
            'Expiry Date',
        ];

        if (config('app.product_variant')) {
            return array_merge($headingColumns, ['attributes']);
        }

        return array_merge($headingColumns, ['color', 'size']);
    }

    private function getArticleNumber(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->article_number : $product->article_number;
    }
}
