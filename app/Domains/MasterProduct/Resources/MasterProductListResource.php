<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\Resources;

use App\Models\Category;
use App\Models\MasterProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MasterProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $masterProduct = $this->resource;

        /** @var Collection $categories */
        $categories = $masterProduct->categories;

        /** @var Carbon $createdAt */
        $createdAt = $masterProduct->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $masterProduct->updated_at;

        $originalCreatedAt = null;

        if ($masterProduct->original_created_at) {
            /** @var Carbon $originalCreatedAt */
            $originalCreatedAt = Carbon::createFromFormat('Y-m-d H:i:s', $masterProduct->original_created_at);
            $originalCreatedAt = $originalCreatedAt->format('d-m-Y h:i:s A');
        }

        return [
            'id' => $masterProduct->id,
            'name' => $masterProduct->name,
            'code' => $masterProduct->code,
            'brand' => $masterProduct->brand,
            'categories' => $categories->map(function ($category): array {
                /** @var Category $itemCategory */
                $itemCategory = $category;

                return [
                    'id' => $itemCategory->id,
                    'name' => $itemCategory->name,
                ];
            }),
            'article_number' => $masterProduct->article_number,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'status' => $masterProduct->status,
            'images' => $this->preparedImages($masterProduct),
            'thumbnail_url' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            'original_created_at' => $originalCreatedAt ?? 'N/A',
        ];
    }

    public function preparedImages(MasterProduct $masterProduct): array
    {
        return [
            'image_urls' => $masterProduct->getDiskBasedMediaUrls('images'),
            'video_urls' => $masterProduct->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
