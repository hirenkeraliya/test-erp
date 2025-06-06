<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Size\SizeQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SizeController extends Controller
{
    public function __construct(
        protected SizeQueries $sizeQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredSizes(Request $request): array
    {
        return [
            'sizes' => $this->sizeQueries->getFilteredSizesByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
