<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Resources;

use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ApplicationDreamPriceListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var DreamPrice $dreamPrice */
        $dreamPrice = $this;

        /** @var Collection $dreamPriceProducts */
        $dreamPriceProducts = $dreamPrice->dreamPriceProducts;

        /** @var Carbon $startDateFormat */
        $startDateFormat = Carbon::createFromFormat('Y-m-d', $dreamPrice->start_date);
        /** @var Carbon $endDateFormat */
        $endDateFormat = Carbon::createFromFormat('Y-m-d', $dreamPrice->end_date);
        $startDate = $startDateFormat->format('d-m-Y');
        $endDate = $endDateFormat->format('d-m-Y');

        return [
            'id' => $dreamPrice->id,
            'name' => $dreamPrice->name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'dream_price_products_count' => $dreamPrice->dreamPriceProducts->count(),
            'products' => $dreamPriceProducts->map(function ($product): array {
                /** @var DreamPriceProduct $dreamPriceProduct */
                $dreamPriceProduct = $product;

                /** @var Product $originalProduct */
                $originalProduct = $dreamPriceProduct->product;

                return [
                    'id' => $dreamPriceProduct->id,
                    'product_id' => $dreamPriceProduct->product_id,
                    'images' => $this->preparedImages($originalProduct),
                    'thumbnail_url' => $originalProduct->getDiskBasedFirstMediaUrl('thumbnail'),
                ];
            }),
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
