<?php

declare(strict_types=1);

namespace App\Domains\Color\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceColorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $color = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $color->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $color->created_at;

        return [
            'id' => $color->id,
            'name' => $color->name,
            'code' => $color->code,
            'hex_code' => $color->color_code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
