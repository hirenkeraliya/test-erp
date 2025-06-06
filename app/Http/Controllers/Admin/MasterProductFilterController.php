<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\MasterProduct\MasterProductQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MasterProductFilterController extends Controller
{
    public function __construct(
        protected MasterProductQueries $masterProductQueries
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getFilteredRegularMasterProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
        ];

        $masterProducts = $this->masterProductQueries->getActiveRegularMasterProductsFilteredByNameBrandAndCategory(
            $filterData,
            session('admin_company_id')
        );

        return [
            'products' => $masterProducts->toArray(),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredRegularMasterProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        return [
            'products' => $this->masterProductQueries->getActiveFilteredRegularMasterProducts(
                $filterData,
                session('admin_company_id')
            ),
        ];
    }

    public function getMasterProduct(int $masterProductId): array
    {
        return [
            'product' => $this->masterProductQueries->getActiveMasterProductWithBasicColumnsById(
                $masterProductId,
                session('admin_company_id')
            ),
        ];
    }
}
