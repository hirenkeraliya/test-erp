<?php

declare(strict_types=1);

namespace App\Domains\Season\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $season = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $season->updated_at;

        return [
            'id' => $season->id,
            'name' => $season->name,
            'code' => $season->code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
