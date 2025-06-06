<?php

declare(strict_types=1);

namespace App\Domains\Store\Resources;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Location $location */
        $location = $this;

        return [
            'id' => $location->getKey(),
            'name' => $location->name,
            'code' => $location->code,
        ];
    }
}
