<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Category\CategoryQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryQueries $categoryQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredCategories(Request $request): array
    {
        return [
            'categories' => $this->categoryQueries->getFilteredCategoriesByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }

    public function getCategoriesList(Request $request): array
    {
        return [
            'categories' => $this->categoryQueries->getWithBasicColumns(
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
