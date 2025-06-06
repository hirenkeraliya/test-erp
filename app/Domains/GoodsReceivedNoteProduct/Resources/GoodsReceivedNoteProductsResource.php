<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNoteProduct\Resources;

use App\Models\Batch;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Product;
use App\Models\PurchaseAmount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceivedNoteProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var GoodsReceivedNoteProduct $goodsReceivedNoteProduct */
        $goodsReceivedNoteProduct = $this;

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

        return [
            'product_upc' => $product->upc,
            'product_name' => $product->name,
            'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
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
            'batch_number' => $batch instanceof Batch ? $batch->number : 'N/A',
        ];
    }

    private function getArticleNumber(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->article_number : $product->article_number;
    }
}
