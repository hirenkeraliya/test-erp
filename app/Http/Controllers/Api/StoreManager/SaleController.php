<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Sale\DataObjects\StoreManagerApiSaleData;
use App\Domains\Sale\Resources\StoreManagerAppSaleDetailsResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\Resources\StoreManagerAppSaleReturnDetailsResource;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Sales\Resources\SalesListForStoreManagerApplicationResource;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleController extends Controller
{
    public function getSales(Request $request, StoreManagerApiSaleData $storeManagerApiSaleData): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $filterData = [
            'per_page' => $storeManagerApiSaleData->per_page,
            'member_id' => $storeManagerApiSaleData->member_id,
            'cashier_id' => $storeManagerApiSaleData->cashier_id,
            'employee_id' => $storeManagerApiSaleData->employee_id,
            'counter_id' => $storeManagerApiSaleData->counter_id,
            'location_id' => $storeManagerApiSaleData->store_id ?? $storeManagerApiSaleData->location_id,
            'start_date' => $storeManagerApiSaleData->start_date,
            'end_date' => $storeManagerApiSaleData->end_date,
            'sort_by' => $storeManagerApiSaleData->sort_by,
            'sort_direction' => $storeManagerApiSaleData->sort_direction,
            'page' => $storeManagerApiSaleData->page,
            'search_text' => $storeManagerApiSaleData->search_text,
        ];

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getSalesForTheStoreManagerApplication($filterData, $companyId);
        $sales->map(fn ($sale): string => $sale->type = 'Sale');

        $totalSaleItems = $sales->pluck('saleItems')->flatten()->sum('quantity');

        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturns = $saleReturnQueries->getSaleReturnsForTheStoreManagerApplication($filterData, $companyId);

        $saleReturns->map(fn ($saleReturn): string => $saleReturn->type = 'Sale Return');

        $totalSaleReturnItems = $saleReturns->pluck('saleReturnItems')->flatten()->sum('quantity');

        $newSales = $sales->merge($saleReturns);

        $lengthAwarePaginator = new LengthAwarePaginator(
            $newSales->forPage($filterData['page'], $filterData['per_page']),
            $newSales->count(),
            $filterData['per_page'],
            $filterData['page']
        );

        $totalSaleAmount = $newSales->where('type', 'Sale')->sum('total_amount_paid');
        $totalSaleReturnAmount = $newSales->where('type', 'Sale Return')->sum('total_price_paid');

        return [
            'data' => SalesListForStoreManagerApplicationResource::collection($lengthAwarePaginator->values()),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
            'total_sale' => $newSales->where('type', 'Sale')->count(),
            'total_sale_items' => $totalSaleItems,
            'total_sale_return' => $newSales->where('type', 'Sale Return')->count(),
            'total_sale_return_items' => $totalSaleReturnItems,
            'total_sale_amount' => CommonFunctions::numberFormat((float) $totalSaleAmount),
            'total_sale_return_amount' => CommonFunctions::numberFormat((float) $totalSaleReturnAmount),
            'net_sale_amount' => CommonFunctions::numberFormat((float) ($totalSaleAmount - $totalSaleReturnAmount)),
        ];
    }

    public function getSaleDetails(Request $request, int $saleId, string $saleType, int $locationId): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        if ($saleType !== ModelMapping::SALE->name && $saleType !== ModelMapping::SALE_RETURN->name) {
            abort(
                412,
                'Specified type is not valid. Use ' . ModelMapping::SALE->name . ' or ' . ModelMapping::SALE_RETURN->name
            );
        }

        if ($saleType === ModelMapping::SALE->name) {
            $saleQueries = resolve(SaleQueries::class);
            $saleItemOrSaleReturnItem = $saleQueries->getSaleItemsForStoreManagerApi($saleId, $locationId, $companyId);

            return [
                'sale_details' => new StoreManagerAppSaleDetailsResource($saleItemOrSaleReturnItem),
            ];
        }

        if ($saleType === ModelMapping::SALE_RETURN->name) {
            $saleReturnQuires = resolve(SaleReturnQueries::class);
            $saleItemOrSaleReturnItem = $saleReturnQuires->getSaleReturnItemsForStoreManagerApi(
                $saleId,
                $locationId,
                $companyId
            );

            return [
                'sale_return_details' => new StoreManagerAppSaleReturnDetailsResource($saleItemOrSaleReturnItem),
            ];
        }
    }
}
