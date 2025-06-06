<?php

declare(strict_types=1);

namespace App\Domains\ExternalConnection\Resource;

use App\Domains\ExternalConnection\Enums\Statuses;
use App\Models\ExternalConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalConnectionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ExternalConnection $externalConnection */
        $externalConnection = $this;

        return [
            'id' => $externalConnection->id,
            'name' => $externalConnection->name,
            'url' => $externalConnection->url,
            'approved_at' => $externalConnection->approved_at,
            'rejected_at' => $externalConnection->rejected_at,
            'status' => Statuses::getFormattedCaseName((int) $externalConnection->status),
        ];
    }
}
