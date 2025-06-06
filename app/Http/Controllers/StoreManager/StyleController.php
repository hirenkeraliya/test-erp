<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
                session('store_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getStylesList(): array
    {
        return [
            'styles' => $this->styleQueries->getWithBasicColumns(session('store_manager_selected_location_company_id')),
        ];
    }
}
