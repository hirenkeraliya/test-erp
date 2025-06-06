<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\Resources\PromoterSalesListCollectionResource;
use App\Domains\Sale\DataObjects\PromoterHistorySaleData;
use App\Domains\Sale\DataObjects\PromoterSaleData;
use App\Domains\SaleItem\Resources\ItemsWiseDetailsApiResource;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleController extends Controller
{
    public function getPaginatedSaleHistory(Request $request, PromoterSaleData $promoterSaleData): array
    {
        $filteredData = [
            'page' => $promoterSaleData->page,
            'per_page' => $promoterSaleData->per_page,
            'sort_by' => $promoterSaleData->sort_by,
            'sort_direction' => $promoterSaleData->sort_direction,
            'location_id' => $promoterSaleData->store_id ?? $promoterSaleData->location_id,
            'start_date' => $promoterSaleData->start_date,
            'end_date' => $promoterSaleData->end_date,
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->loadEmployee($promoter);

        /** @var Employee $employee */
        $employee = $promoter->employee;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($employee->company_id);

        $lengthAwarePaginator = $promoterQueries->getPromotersWiseSales($filteredData, $promoter->id);

        return [
            'sale_history' => new PromoterSalesListCollectionResource(
                $lengthAwarePaginator->getCollection(),
                $currency->getSymbol()
            ),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function getSaleHistoryBySingleDate(
        Request $request,
        PromoterHistorySaleData $promoterHistorySaleData
    ): array {
        $filteredData = [
            'page' => $promoterHistorySaleData->page,
            'per_page' => $promoterHistorySaleData->per_page,
            'start_date' => $promoterHistorySaleData->selected_date,
            'end_date' => $promoterHistorySaleData->selected_date,
            'location_id' => $promoterHistorySaleData->store_id ?? $promoterHistorySaleData->location_id,
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->loadEmployee($promoter);

        /** @var Employee $employee */
        $employee = $promoter->employee;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($employee->company_id);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $promoterSales = $saleItemQueries->getPromotersWiseSales($filteredData, $promoter->id);

        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $promoterSaleReturns = $saleReturnItemQueries->getPromoterWiseSalesReturnItems($filteredData, $promoter->id);

        $transactions = $promoterSales->merge($promoterSaleReturns);

        $saleAndReturns = collect([]);

        foreach ($transactions as $transaction) {
            $amount = 0;
            $saleType = null;

            if (isset($transaction->quantity)) {
                if ($transaction instanceof SaleReturnItem) {
                    $amount = CommonFunctions::currencySymbolDisplayWithAmount(
                        $currency->getSymbol(),
                        (float) $transaction->total_price_paid,
                        true
                    );
                    $saleType = ModelMapping::SALE_RETURN->name;
                } elseif ($transaction instanceof SaleItem) {
                    $amount = CommonFunctions::currencySymbolDisplayWithAmount(
                        $currency->getSymbol(),
                        (float) $transaction->total_price_paid
                    );
                    $saleType = ModelMapping::SALE->name;
                }
            }

            $saleAndReturns->push([
                'id' => $transaction->id,
                'receipt_id' => $transaction->receipt_id,
                'unit_sold' => $transaction->quantity,
                'amount' => $amount,
                'status' => $saleType,
            ]);
        }

        $lengthAwarePaginator = new LengthAwarePaginator(
            $saleAndReturns->forPage($filteredData['page'], $filteredData['per_page']),
            $saleAndReturns->count(),
            $filteredData['per_page'],
            $filteredData['page']
        );

        return [
            'summary' => [
                'date' => $filteredData['start_date'],
                'items_sold' => $saleAndReturns->where('status', ModelMapping::SALE->name)->sum('unit_sold'),
                'items_returned' => $saleAndReturns->where(
                    'status',
                    ModelMapping::SALE_RETURN->name
                )->sum('unit_sold') ?? 0,
                'net_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currency->getSymbol(),
                    $promoterSales->sum('total_price_paid') - $promoterSaleReturns->sum('total_price_paid')
                ),
            ],
            'sales' => $lengthAwarePaginator->getCollection()->values(),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function getItemDetails(Request $request, int $itemId, string $saleType): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->loadEmployee($promoter);

        /** @var Employee $employee */
        $employee = $promoter->employee;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($employee->company_id);

        if ($saleType !== ModelMapping::SALE->name && $saleType !== ModelMapping::SALE_RETURN->name) {
            abort(
                412,
                'Specified type is not valid. Use ' . ModelMapping::SALE->name . ' or ' . ModelMapping::SALE_RETURN->name
            );
        }

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);

        $saleItemOrSaleReturnItem = [];

        if ($saleType === ModelMapping::SALE->name) {
            $saleItemOrSaleReturnItem = $saleItemQueries->getSaleItemWithProductsAndPromoters($itemId, $promoter->id);
        }

        if ($saleType === ModelMapping::SALE_RETURN->name) {
            $saleItemOrSaleReturnItem = $saleReturnItemQueries->getSaleReturnItemWithProductAndPromoters(
                $itemId,
                $promoter->id
            );
        }

        return [
            'details' => new ItemsWiseDetailsApiResource($saleItemOrSaleReturnItem, $currency->getSymbol()),
        ];
    }
}
