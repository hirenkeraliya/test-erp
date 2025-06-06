<?php

declare(strict_types=1);

namespace App\Domains\Vehicle\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->resource;

        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'plate_no' => $vehicle->plate_no,
            'type_of_vehicle' => $vehicle->type_of_vehicle,
            'status' => $vehicle->status,
        ];
    }
}
