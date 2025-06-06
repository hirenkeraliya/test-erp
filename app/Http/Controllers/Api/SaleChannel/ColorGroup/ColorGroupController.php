<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\ColorGroup;

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\Resources\EcommerceColorGroupResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class ColorGroupController extends Controller
{
    public function __construct(
        protected ColorGroupQueries $colorGroupQueries
    ) {
    }

    public function getColorGroupList(Request $request): array
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
            'search_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'after_updated_at' => $validatedData['after_updated_at'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
            'search_text' => $validatedData['search_text'] ?? '',
        ];

        $colorGroups = $this->colorGroupQueries->getColorGroupsByCompanyId($saleChannel->getCompanyId(), $filteredData);

        return [
            'colorGroups' => EcommerceColorGroupResource::collection($colorGroups->getCollection()),
            'total_records' => $colorGroups->total(),
            'last_page' => $colorGroups->lastPage(),
            'current_page' => $colorGroups->currentPage(),
            'per_page' => $colorGroups->perPage(),
        ];
    }
}
