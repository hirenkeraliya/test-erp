<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Region\RegionQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function getAllRegions(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $regionQueries = resolve(RegionQueries::class);

        return [
            'regions' => $regionQueries->getAllByCompanyId($companyId),
        ];
    }
}
