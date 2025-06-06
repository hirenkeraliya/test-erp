<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Sale\DataObjects\RetailPlanningRegularSaleByDateData;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Sales\Resources\RetailPlanningSalesListResource;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function getAllAggregatedSales(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var SaleItemQueries $saleItemQueries */
        $saleItemQueries = resolve(SaleItemQueries::class);

        $sales = $saleItemQueries->getRegularProductAggregateSales($companyId);

        return [
            'sales' => RetailPlanningSalesListResource::collection($sales),
            'current_page' => $sales->currentPage(),
            'last_page' => $sales->lastPage(),
        ];
    }

    public function getAggregatedRegularSalesForSpecifiedDate(
        RetailPlanningRegularSaleByDateData $RetailPlanningRegularSaleByDateData,
        Request $request
    ): array {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var SaleItemQueries $saleItemQueries */
        $saleItemQueries = resolve(SaleItemQueries::class);

        $closedCounterSales = $saleItemQueries->getRegularProductSalesAggregateForClosedCounter(
            $companyId,
            $RetailPlanningRegularSaleByDateData->dates
        );

        return [
            'sales' => RetailPlanningSalesListResource::collection($closedCounterSales),
            'current_page' => $closedCounterSales->currentPage(),
            'last_page' => $closedCounterSales->lastPage(),
        ];
    }

    public function getCompleteLayawayAndCreditAggregatedSalesForSpecifiedDate(
        RetailPlanningRegularSaleByDateData $RetailPlanningRegularSaleByDateData,
        Request $request
    ): array {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var SaleItemQueries $saleItemQueries */
        $saleItemQueries = resolve(SaleItemQueries::class);
        $completeLayawayAndCreditSales = $saleItemQueries->getRegularProductCompleteCreditAndLayawaySalesAggregateForClosedCounter(
            $companyId,
            $RetailPlanningRegularSaleByDateData->dates
        );

        return [
            'completeLayawayAndCreditSales' => $completeLayawayAndCreditSales,
        ];
    }
}
