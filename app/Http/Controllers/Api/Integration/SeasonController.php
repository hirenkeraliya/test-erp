<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Season\SeasonQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function getAllSeasons(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var SeasonQueries $seasonQueries */
        $seasonQueries = resolve(SeasonQueries::class);

        return [
            'seasons' => $seasonQueries->getAllSeasonByCompanyId($companyId),
        ];
    }
}
