<?php

declare(strict_types=1);

namespace App\Domains\Counter\Resources;

use App\Models\Counter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCounterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Counter $counter */
        $counter = $this;

        return [
            'id' => $counter->getKey(),
            'name' => $counter->name,
            'store_id' => $counter->location_id,
            'location_id' => $counter->location_id,
            'is_locked' => $counter->is_locked,
            'is_opened' => (bool) $counter->counter_update_id,
            'is_self_checkout' => $counter->is_self_checkout,
        ];
    }
}
