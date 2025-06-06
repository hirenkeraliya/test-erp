<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductCollectionController extends Controller
{
    public function __construct(
        protected ProductCollectionQueries $productCollectionQueries
    ) {
    }

    public function getFilteredProductCollections(Request $request): array
    {
        return [
            'productCollections' => $this->productCollectionQueries->getFilteredProductCollectionsByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
