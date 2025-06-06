<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $memberAddress = $this->resource;

        return [
            'id' => $memberAddress->id,
            'member_id' => $memberAddress->member_id,
            'name' => $memberAddress->name,
            'first_name' => $memberAddress->first_name,
            'last_name' => $memberAddress->last_name,
            'contact_mobile_number' => $memberAddress->contact_mobile_number,
            'contact_email' => $memberAddress->contact_email,
            'address_line_1' => $memberAddress->address_line_1,
            'address_line_2' => $memberAddress->address_line_2,
            'city_name' => $memberAddress->city_name,
            'country_id' => $memberAddress->country_id,
            'state_id' => $memberAddress->state_id,
            'city_id' => $memberAddress->city_id,
            'area_code' => $memberAddress->area_code,
            'is_primary' => $memberAddress->is_primary,
        ];
    }
}
