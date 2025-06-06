<?php

declare(strict_types=1);

namespace App\Domains\Membership\Resources;

use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosMemberShipListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Membership $membership */
        $membership = $this;

        return [
            'id' => $membership->id,
            'name' => $membership->name,
            'minimum_lifetime_spend_amount' => $membership->lifetime_value,
            'lifetime_value' => $membership->lifetime_value,
            'loyalty_points_per_ringgit' => $membership->loyalty_points_per_currency_unit,
            'loyalty_points_per_one_currency_unit' => $membership->loyalty_points_per_currency_unit,
            'loyalty_points_per_currency_unit' => $membership->loyalty_points_per_currency_unit,
            'min_loyalty_points_for_redemption' => $membership->min_loyalty_points_for_redemption,
            'max_loyalty_points_for_redemption' => $membership->max_loyalty_points_for_redemption,
        ];
    }
}
