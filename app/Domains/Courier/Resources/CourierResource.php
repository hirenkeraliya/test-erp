<?php

declare(strict_types=1);

namespace App\Domains\Courier\Resources;

use App\Models\CourierWebhookUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $courier = $this->resource;

        return [
            'id' => $courier->id,
            'name' => $courier->getName(),
            'code' => $courier->getCode(),
            'type_id' => $courier->type_id->value,
            'url' => $courier->getUrl(),
            'client_id' => $courier->client_id,
            'client_secret' => $courier->client_secret,
            'webhook_urls' => $courier->courierWebhookUrls->map(
                fn (CourierWebhookUrl $courierWebhookUrl): array => [
                    'webhook_url_type_id' => $courierWebhookUrl->webhook_url_type_id,
                    'url' => $courierWebhookUrl->url,
                ]
            )->toArray(),
        ];
    }
}
