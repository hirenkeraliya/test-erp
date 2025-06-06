<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LocationController extends Controller
{
    public function getLocations(Request $request): Collection
    {
        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getByCompanyId((int) $request->company_id);

        return $locations->map(
            fn (Location $location): array => [
                'id' => $location->id,
                'type_id' => $location->type_id,
                'name' => $location->name,
                'code' => $location->code,
                'email' => $location->email,
                'phone' => $location->phone,
                'address_line_1' => $location->address_line_1,
                'address_line_2' => $location->address_line_2,
                'city' => $location->city?->name,
                'area_code' => $location->area_code,
                'fax' => $location->fax,
            ]
        );
    }
}
