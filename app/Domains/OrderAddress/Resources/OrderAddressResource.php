<?php

declare(strict_types=1);

namespace App\Domains\OrderAddress\Resources;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderAddressResource extends JsonResource
{
    public function __construct(
        $resource,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $orderAddress = $this->resource;

        /** @var City $city */
        $city = $orderAddress->city;

        $cityName = $orderAddress->city_name;
        if (null !== $city) {
            $cityName = $city->name;
        }

        return [
            'id' => $orderAddress->getKey(),
            'first_name' => $orderAddress->first_name,
            'last_name' => $orderAddress->last_name,
            'address_line_1' => $orderAddress->address_line_1,
            'address_line_2' => $orderAddress->address_line_2,
            'phone' => $orderAddress->phone,
            'area_code' => $orderAddress->area_code,
            'city_name' => $cityName,
        ];
    }
}
