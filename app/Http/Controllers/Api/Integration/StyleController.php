<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class StyleController extends Controller
{
    public function getAllStyles(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $styleQueries = resolve(StyleQueries::class);

        return [
            'styles' => $styleQueries->getAllByCompanyId($companyId),
        ];
    }
}
