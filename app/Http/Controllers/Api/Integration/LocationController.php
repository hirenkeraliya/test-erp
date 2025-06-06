<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Location\LocationQueries;
use App\Domains\Location\Resources\RetailPlanningLocationListResource;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getAllStoreLocations(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var LocationQueries $locationQueries */
        $locationQueries = resolve(LocationQueries::class);

        return [
            'locations' => RetailPlanningLocationListResource::collection(
                $locationQueries->getAllStoreLocationByCompanyIdWithRelation($companyId)
            ),
        ];
    }
}
