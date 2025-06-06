<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\Resources;

use App\Models\MemberAddress;
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
        /** @var MemberAddress $memberAddress */
        $memberAddress = $this;

        // Use city name from relationship if available, fallback to city_name field
        $cityName = $memberAddress->city_name;
        if ($memberAddress->city) {
            $cityName = $memberAddress->city->name;
        }

        $response = [
            'id' => $memberAddress->id,
            'member_id' => $memberAddress->member_id,
            'name' => $memberAddress->name,
            'contact_mobile_number' => $memberAddress->contact_mobile_number,
            'contact_email' => $memberAddress->contact_email,
            'address_line_1' => $memberAddress->address_line_1,
            'address_line_2' => $memberAddress->address_line_2,
            'city' => $cityName,
            'area_code' => $memberAddress->area_code,
            'is_primary' => $memberAddress->is_primary,
            'created_at' => $memberAddress->created_at,
            'updated_at' => $memberAddress->updated_at,
        ];

        // Add new location fields if they exist
        if ($memberAddress->country) {
            $response['country'] = [
                'id' => $memberAddress->country_id,
                'name' => $memberAddress->country->name,
            ];
        }

        if ($memberAddress->state) {
            $response['state'] = [
                'id' => $memberAddress->state_id,
                'name' => $memberAddress->state->name,
            ];
        }

        if ($memberAddress->city) {
            $response['city_details'] = [
                'id' => $memberAddress->city_id,
                'name' => $memberAddress->city->name,
            ];
        }

        return $response;
    }
}
