<?php

declare(strict_types=1);

namespace App\Domains\Country\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EcommerceCountryListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $country = $this->resource;

        /** @var Collection $states */
        $states = $country->states;

        return [
            'id' => $country->id,
            'name' => $country->name,
            'states' => $this->getStates($states),
        ];
    }

    private function getStates(Collection $states): array
    {
        return $states->transform(function ($state): array {
            /** @var Collection $cities */
            $cities = $state->cities;

            return [
                'id' => $state->id,
                'country_id' => $state->country_id,
                'name' => $state->name,
                'country_code' => $state->country_code,
                'cities' => $this->getCities($cities),
            ];
        })->toArray();
    }

    private function getCities(Collection $cities): array
    {
        return $cities->transform(fn ($city): array => [
            'id' => $city->id,
            'name' => $city->name,
        ])->toArray();
    }
}
