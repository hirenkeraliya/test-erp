<?php

declare(strict_types=1);

namespace App\Domains\Driver\Resources;

use App\Models\Driver;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Driver $driver */
        $driver = $this->resource;

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'id_number' => $driver->id_number,
            'email' => $driver->email,
            'mobile_number' => $driver->mobile_number,
            'country_code' => $driver->country_code,
            'status' => $driver->status,
        ];
    }
}
