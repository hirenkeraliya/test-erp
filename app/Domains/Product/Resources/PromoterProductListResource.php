<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\CommonFunctions;
use App\Domains\Product\Services\ProductService;
use App\Models\Inventory;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterProductListResource extends JsonResource
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

        /** @var Inventory $inventory */
        $inventory = $product->inventory;

        $productService = resolve(ProductService::class);

        $masterProductArray = null;
        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct instanceof MasterProduct) {
            $masterProductArray = [
                'id' => $masterProduct->id,
                'name' => $masterProduct->name,
                'article_number' => (string) $masterProduct->article_number,
                'images' => $this->preparedImages($masterProduct),
                'thumbnail_url' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => $product->color?->name ?? 'N/A',
            'size' => $product->size?->name ?? 'N/A',
            'upc' => $product->upc,
            'price' => (float) $product->retail_price,
            'stock' => CommonFunctions::truncateDecimal((float) $inventory->stock),
            'stock_label' => $product->inventory['stock_label'] ?? null,
            'images' => $this->preparedImages($product),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'attributes' => $productService->getAttributesArrayForApi($product),
            'master_product' => $masterProductArray,
        ];
    }

    public function preparedImages(Product|MasterProduct $product): array
    {
        return [
            'image_urls' => $product->getDiskBasedMediaUrls('images'),
            'video_urls' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
