<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Location\LocationQueries;
use App\Domains\SaleReturn\DataObjects\FilteredAndPaginatedSaleReturnsDataForPos;
use App\Domains\SaleReturn\Resources\PosSaleReturnResource;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getFilteredAndPaginatedSaleReturns(
        Request $request,
        FilteredAndPaginatedSaleReturnsDataForPos $filteredAndPaginatedSaleReturnsDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $filterData = [
            'per_page' => $filteredAndPaginatedSaleReturnsDataForPos->per_page,
            'member_id' => $filteredAndPaginatedSaleReturnsDataForPos->member_id,
            'employee_id' => $filteredAndPaginatedSaleReturnsDataForPos->employee_id,
            'from_date' => $filteredAndPaginatedSaleReturnsDataForPos->from_date,
            'to_date' => $filteredAndPaginatedSaleReturnsDataForPos->to_date,
            'search_text' => $filteredAndPaginatedSaleReturnsDataForPos->search_text,
            'sort_by' => $filteredAndPaginatedSaleReturnsDataForPos->sort_by,
            'sort_direction' => $filteredAndPaginatedSaleReturnsDataForPos->sort_direction,
            'after_updated_at' => $filteredAndPaginatedSaleReturnsDataForPos->after_updated_at,
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->getCounterUpdateId());

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $returnSales = $saleReturnQueries->getPaginatedSaleReturnsWithAllRelations(
            $filterData,
            $location->id,
            $companyId
        );

        return [
            'sale_returns' => PosSaleReturnResource::collection($returnSales),
            'total_records' => $returnSales->total(),
            'last_page' => $returnSales->lastPage(),
            'current_page' => $returnSales->currentPage(),
            'per_page' => $returnSales->perPage(),
        ];
    }
}
