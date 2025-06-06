<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\CommonFunctions;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $product = $this->resource;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => $product->color?->name ?? 'N/A',
            'size' => $product->size?->name ?? 'N/A',
            'upc' => $product->upc,
            'retail_price' => (float) $product->retail_price,
            'stock' => CommonFunctions::truncateDecimal((float) $product->inventory?->stock),
            'stock_label' => $product->inventory['stock_label'] ?? null,
            'images' => $this->preparedImages($product),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }

    public function preparedImages(Product $product): array
    {
        return [
            'image_urls' => $product->getDiskBasedMediaUrls('images'),
            'video_urls' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
