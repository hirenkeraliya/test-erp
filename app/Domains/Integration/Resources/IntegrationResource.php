<?php

declare(strict_types=1);

namespace App\Domains\Integration\Resources;

use App\Models\IntegrationWebhookUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $integration = $this->resource;

        return [
            'id' => $integration->id,
            'name' => $integration->getName(),
            'company_id' => $integration->getCompanyId(),
            'connection_type' => $integration->connection_type->value,
            'url' => $integration->getUrl(),
            'secret' => $integration->getSecret(),
            'webhook_urls' => $integration->integrationWebhookUrls->map(
                fn (IntegrationWebhookUrl $integrationWebhookUrl): array => [
                    'webhook_url_type_id' => $integrationWebhookUrl->webhook_url_type_id,
                    'url' => $integrationWebhookUrl->url,
                ]
            )->toArray(),
        ];
    }
}
