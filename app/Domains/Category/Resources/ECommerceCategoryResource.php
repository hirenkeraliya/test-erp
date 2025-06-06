<?php

declare(strict_types=1);

namespace App\Domains\Category\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ECommerceCategoryResource extends JsonResource
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

        /** @var Carbon $createdAt */
        $createdAt = $category->created_at;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
            'parent_category_id' => $category->parent_category_id,
            'image' => $category->getDiskBasedFirstMediaUrl('image'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
