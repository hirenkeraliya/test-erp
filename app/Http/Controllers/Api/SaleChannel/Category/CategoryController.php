<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Category;

use App\Domains\Category\CategoryQueries;
use App\Domains\Category\Resources\ECommerceCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryQueries $categoryQueries
    ) {
    }

    public function getCategoriesList(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'sort_by' => ['sometimes', 'string', 'in:id,name,code'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
        ];

        $categories = $this->categoryQueries->getCategoriesByCompanyId($filteredData, $saleChannel->getCompanyId());

        return [
            'categories' => ECommerceCategoryResource::collection($categories->getCollection()),
            'total_records' => $categories->total(),
            'last_page' => $categories->lastPage(),
            'current_page' => $categories->currentPage(),
            'per_page' => $categories->perPage(),
        ];
    }
}
