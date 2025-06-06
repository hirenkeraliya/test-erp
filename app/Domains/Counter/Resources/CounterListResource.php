<?php

declare(strict_types=1);

namespace App\Domains\Counter\Resources;

use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounterListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $counter = $this->resource;

        /** @var Location $location */
        $location = $counter->location;

        $appVersionUpdatedAt = $counter->app_version_updated_at;
        $formattedDate = 'N/A';

        if ($appVersionUpdatedAt) {
            /** @var Carbon $carbon */
            $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $appVersionUpdatedAt);
            $formattedDate = $carbon->format('d-m-Y h:i:s A');
        }

        return [
            'id' => $counter->getKey(),
            'name' => $counter->getName(),
            'is_locked' => $counter->is_locked,
            'app_version' => $counter->app_version,
            'last_updated_at' => $formattedDate,
            'location' => $location,
        ];
    }
}
