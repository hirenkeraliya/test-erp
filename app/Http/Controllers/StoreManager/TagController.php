<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
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
                session('store_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getTagsList(): array
    {
        return [
            'tags' => $this->tagQueries->getWithBasicColumns(session('store_manager_selected_location_company_id')),
        ];
    }
}
