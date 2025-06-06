<?php

declare(strict_types=1);

namespace App\Domains\Style\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StyleWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $style = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $style->updated_at;

        return [
            'id' => $style->id,
            'name' => $style->name,
            'code' => $style->code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
