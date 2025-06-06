<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

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
                session('admin_company_id')
            ),
        ];
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getBrands(): array
    {
        return [
            'brands' => $this->brandQueries->getCompanyBrands(session('admin_company_id')),
        ];
    }

    public function getBrandSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $brands = $this->brandQueries->getBrandSalesSummary($filterData, session('admin_company_id'));

        return [
            'brands' => $brands,
            'total_sales' => $brands->sum('total_sales'),
            'total_units_sold' => $brands->sum('total_units_sold'),
        ];
    }
}
