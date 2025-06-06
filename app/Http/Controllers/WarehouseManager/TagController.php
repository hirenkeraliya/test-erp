<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(
        protected TagQueries $tagQueries
    ) {
    }

    public function getFilteredTags(Request $request): array
    {
        return [
            'tags' => $this->tagQueries->getFilteredTagsByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }
}
