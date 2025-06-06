<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges\Resources;

use App\Domains\OnlineSalesCharges\Enums\ShippingChargeTypes;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineSalesChargesListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $onlineSalesCharge = $this->resource;

        /** @var ShippingZone $shippingZone */
        $shippingZone = $onlineSalesCharge->shippingZone;

        return [
            'id' => $onlineSalesCharge->id,
            'name' => $onlineSalesCharge->name,
            'zone' => $shippingZone->name,
            'shipping_charges_type' => ShippingChargeTypes::getFormattedCaseName(
                $onlineSalesCharge->shipping_charge_type_id
            ),
            'status' => $onlineSalesCharge->status,
        ];
    }
}
