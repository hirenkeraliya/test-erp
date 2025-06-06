<?php

declare(strict_types=1);

namespace App\Domains\EmailTemplate\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $emailTemplate = $this->resource;

        /** @var Carbon $createdAt */
        $createdAt = $emailTemplate->created_at;

        return [
            'id' => $emailTemplate->id,
            'name' => $emailTemplate->name,
            'usage' => $emailTemplate->usage,
            'clicks' => $emailTemplate->clicks,
            'revenue' => $emailTemplate->revenue,
            'conversion' => $emailTemplate->conversion,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
        ];
    }
}
