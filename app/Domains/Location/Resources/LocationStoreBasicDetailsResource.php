<?php

declare(strict_types=1);

namespace App\Domains\Location\Resources;

use App\Models\City;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationStoreBasicDetailsResource extends JsonResource
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

        /** @var City $city */
        $city = $location->city;

        return [
            'id' => $location->getKey(),
            'name' => $location->name,
            'code' => $location->code,
            'email' => $location->email,
            'phone' => $location->phone,
            'mobile' => $location->mobile,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'city' => $city->name ?? 'N/A',
            'area_code' => $location->area_code,
            'sales_tax_percentage' => (float) $location->sales_tax_percentage,
            'sales_return_days_limit' => $location->sales_return_days_limit,
            'receipt_footer' => $location->receipt_footer,
            'disclaimer' => $location->disclaimer,
        ];
    }
}
