<?php

declare(strict_types=1);

namespace App\Domains\ColorGroup\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceColorGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $colorGroup = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $colorGroup->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $colorGroup->created_at;

        return [
            'id' => $colorGroup->id,
            'name' => $colorGroup->name,
            'code' => $colorGroup->code,
            'color_code' => $colorGroup->color_code,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
