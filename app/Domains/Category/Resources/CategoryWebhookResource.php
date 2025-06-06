<?php

declare(strict_types=1);

namespace App\Domains\Category\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $category = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $category->updated_at;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'parent_category_id' => $category->parent_category_id,
            'status' => $category->status,
            'is_available_in_ecommerce' => $category->is_available_in_ecommerce,
            'is_display_on_menu' => $category->is_display_on_menu,
            'square_url' => $category->getDiskBasedFirstMediaUrl('square_image'),
            'portrait_urls' => $category->getDiskBasedMediaUrls('portrait_images'),
            'landscape_urls' => $category->getDiskBasedMediaUrls('landscape_images'),
            'code' => $category->code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
