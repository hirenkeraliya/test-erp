<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\Resources;

use App\Domains\Common\Enums\Statuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCollectionDeleteWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productCollection = $this->resource;

        /** @var Carbon $createdAt */
        $createdAt = $productCollection->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $productCollection->updated_at;

        return [
            'id' => $productCollection->id,
            'name' => $productCollection->name,
            'images' => [
                'square_url' => $productCollection->getDiskBasedFirstMediaUrl('square_image'),
                'square_url_detail' => $productCollection->getIdAndName('square_image'),
                'portrait_urls' => $productCollection->getDiskBasedMediaUrls('portrait_images'),
                'portrait_url_details' => $productCollection->getDiskBasedMediaIdAndNames('portrait_images'),
                'landscape_urls' => $productCollection->getDiskBasedMediaUrls('landscape_images'),
                'landscape_url_details' => $productCollection->getDiskBasedMediaIdAndNames('landscape_images'),
            ],
            'status' => Statuses::getFormattedCaseName(Statuses::INACTIVE->value),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'deleted_at' => $productCollection->deleted_at->format('Y-m-d H:i:s'),
        ];
    }
}
