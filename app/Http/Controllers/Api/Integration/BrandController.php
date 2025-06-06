<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Brand\BrandQueries;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Integration;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function getAllBrands(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getAllByCompanyId($companyId);

        $brands = $brands->transform(function (Brand $brand) use ($companyId): Brand {
            $brand['company_id'] = $companyId;

            return $brand;
        });

        return [
            'brands' => $brands,
        ];
    }
}
