<?php

declare(strict_types=1);

namespace App\Domains\Store\Resources;

use App\Models\City;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreListForMemberAppResource extends JsonResource
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

        /** @var Region $region */
        $region = $location->region;

        /** @var City $city */
        $city = $location->city;

        return [
            'id' => $location->getKey(),
            'name' => $location->name,
            'code' => $location->code,
            'region' => $region->name ?? 'N/A',
            'registration_number' => $location->registration_number,
            'sst_number' => $location->sst_number,
            'email' => $location->email,
            'phone' => $location->phone,
            'mobile' => $location->mobile,
            'fax' => $location->fax,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'city' => $city->name ?? 'N/A',
            'area_code' => $location->area_code,
            'website' => $location->web_site,
            'open_time' => $location->open_time,
            'close_time' => $location->close_time,
        ];
    }
}
