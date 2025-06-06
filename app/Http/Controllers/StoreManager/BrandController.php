<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Brand\BrandQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BrandController extends Controller
{
    public function __construct(
        protected BrandQueries $brandQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredBrands(Request $request): array
    {
        return [
            'brands' => $this->brandQueries->getFilteredBrandsByCompanyId(
                $request->input('search_text'),
                session('store_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getBrands(): array
    {
        return [
            'brands' => $this->brandQueries->getCompanyBrands(session('store_manager_selected_location_company_id')),
        ];
    }
}
