<?php

declare(strict_types=1);

namespace App\Domains\Size\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SizeWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $size = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $size->updated_at;

        return [
            'id' => $size->id,
            'name' => $size->name,
            'code' => $size->code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
