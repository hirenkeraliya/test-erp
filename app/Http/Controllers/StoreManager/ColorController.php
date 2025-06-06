<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Color\ColorQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ColorController extends Controller
{
    public function __construct(
        protected ColorQueries $colorQueries
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredColors(Request $request): array
    {
        return [
            'colors' => $this->colorQueries->getFilteredColorsByCompanyId(
                $request->input('search_text'),
                session('store_manager_selected_location_company_id')
            ),
        ];
    }
}
