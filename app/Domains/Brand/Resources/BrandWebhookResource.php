<?php

declare(strict_types=1);

namespace App\Domains\Brand\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $brand = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $brand->updated_at;

        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'code' => $brand->code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
