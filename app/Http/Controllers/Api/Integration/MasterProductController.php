<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\MasterProduct\MasterProductQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class MasterProductController extends Controller
{
    public function getAllMasterProducts(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $masterProductQueries = resolve(MasterProductQueries::class);

        return [
            'products' => $masterProductQueries->getAllByCompanyId($companyId),
        ];
    }

    public function getAllProductsCount(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $masterProductQueries = resolve(MasterProductQueries::class);

        return [
            'total_products' => $masterProductQueries->getCompanyActiveRegularMasterProductCount($companyId),
        ];
    }
}
