<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StyleController extends Controller
{
    public function __construct(
        protected StyleQueries $styleQueries
    ) {
    }

    public function getFilteredStyles(Request $request): array
    {
        return [
            'styles' => $this->styleQueries->getFilteredStylesByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
