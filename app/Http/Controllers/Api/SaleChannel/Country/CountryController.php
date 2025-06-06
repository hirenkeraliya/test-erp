<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SaleChannel\Country;

use App\Domains\Country\CountryQueries;
use App\Domains\Country\Resources\EcommerceCountryListResource;
use App\Http\Controllers\Controller;
use App\Models\SaleChannel;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function __construct(
        protected CountryQueries $countryQueries
    ) {
    }

    public function getCountryList(Request $request): array
    {
        $saleChannel = $request->user();

        if (! $saleChannel instanceof SaleChannel) {
            abort(401, 'You are not authenticated.');
        }

        $validatedData = $request->validate([
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer'],
            'sort_by' => ['sometimes', 'string', 'in:id,name'],
            'sort_direction' => ['required_with:sort_by', 'string', 'in:asc,desc'],
        ]);

        $filteredData = [
            'per_page' => $validatedData['per_page'] ?? null,
            'sort_by' => $validatedData['sort_by'] ?? null,
            'sort_direction' => $validatedData['sort_direction'] ?? null,
        ];

        $countries = $this->countryQueries->getCountryForEcommerce($filteredData);

        return [
            'countries' => EcommerceCountryListResource::collection($countries->getCollection()),
            'total_records' => $countries->total(),
            'last_page' => $countries->lastPage(),
            'current_page' => $countries->currentPage(),
            'per_page' => $countries->perPage(),
        ];
    }
}
