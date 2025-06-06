<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Resources;

use App\Models\MysteryGift;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class AdminEditMysteryGiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MysteryGift $mysteryGift */
        $mysteryGift = $this;

        /** @var Collection ?$mysteryGiftProducts */
        $mysteryGiftProducts = $mysteryGift->mysteryGiftProducts;

        return [
            'id' => $mysteryGift->id,
            'name' => $mysteryGift->name,
            'min_flat_amount' => $mysteryGift->min_flat_amount,
            'max_flat_amount' => $mysteryGift->max_flat_amount,
            'min_percentage' => $mysteryGift->min_percentage,
            'max_percentage' => $mysteryGift->max_percentage,
            'is_flat_amount' => $mysteryGift->is_flat_amount,
            'is_percentage' => $mysteryGift->is_percentage,
            'is_free_product' => $mysteryGift->is_free_product,
            'start_date' => $mysteryGift->start_date,
            'end_date' => $mysteryGift->end_date,
            'minimum_spend' => $mysteryGift->minimum_spend,
            'uploaded_products' => $mysteryGiftProducts->isNotEmpty() ? $this->getSelectedProductDetails(
                $mysteryGiftProducts
            ) : [],
            'minimum_spend_amount_for_free_product' => $mysteryGift->minimum_spend_amount_for_free_product,
            'minimum_spend_amount_for_percentage' => $mysteryGift->minimum_spend_amount_for_percentage,
            'minimum_spend_amount_for_flat_amount' => $mysteryGift->minimum_spend_amount_for_flat_amount,
        ];
    }

    /**
     * @return mixed[]
     */
    public function getSelectedProductDetails(Collection $mysteryGiftProducts): array
    {
        return $mysteryGiftProducts->map(function ($mysteryGiftProduct): array {
            /** @var ?Product $product */
            $product = $mysteryGiftProduct->product;

            return [
                'id' => $product?->id,
                'name' => $product?->name,
                'upc' => $product?->upc,
                'color_name' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
                'size_name' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'quantity' => $mysteryGiftProduct->quantity,
            ];
        })->toArray();
    }
}
