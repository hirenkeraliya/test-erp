<?php

declare(strict_types=1);

namespace App\Domains\Integration\Resources;

use App\Domains\Integration\Enums\IntegrationConnections;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationListResource extends JsonResource
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
            'connection_type' => IntegrationConnections::getFormattedCaseName($integration->connection_type->value),
            'url' => $integration->getUrl(),
            'status' => $integration->status,
        ];
    }
}
