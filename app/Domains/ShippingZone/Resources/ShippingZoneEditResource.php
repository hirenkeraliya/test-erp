<?php

declare(strict_types=1);

namespace App\Domains\ShippingZone\Resources;

use App\Models\Country;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ShippingZone $shippingZone */
        $shippingZone = $this;

        /** @var Country $country */
        $country = $shippingZone->country;

        return [
            'id' => $shippingZone->id,
            'name' => $shippingZone->name,
            'country_id' => $shippingZone->country_id,
            'selected_states' => $shippingZone->states,
            'states' => $country->states,
        ];
    }
}
