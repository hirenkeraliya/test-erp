<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\DataObjects\PromoterCommissionData;
use App\Domains\PromoterCommission\Resources\PromoterCommissionDetailsResource;
use App\Domains\PromoterCommission\Resources\PromoterCommissionListApiCollectionResource;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Domains\PromoterCommissionUpdate\Resources\PromoterCommissionHistoryListResource;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PromoterCommissionController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedPromoterCommissionHistory(
        Request $request,
        PromoterCommissionData $promoterCommissionData
    ): array {
        $now = Carbon::now()->subMonth();

        $dateRange = [$now->startOfMonth()->format('Y-m-d'), $now->endOfMonth()->format('Y-m-d')];

        if ($promoterCommissionData->start_date || $promoterCommissionData->end_date) {
            $dateRange = [$promoterCommissionData->start_date, $promoterCommissionData->end_date];
        }

        $filteredData = [
            'per_page' => $promoterCommissionData->per_page,
            'page' => $promoterCommissionData->page,
            'sort_by' => $promoterCommissionData->sort_by,
            'sort_direction' => $promoterCommissionData->sort_direction,
            'date_range' => $dateRange,
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        /** @var int $promoterCommissionDataLocationId */
        $promoterCommissionDataLocationId = $promoterCommissionData->store_id ?? $promoterCommissionData->location_id;

        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);
        $promoterCommissionUpdatesData = $promoterCommissionUpdateQueries->promoterCommissionBasedOnPromoterAndLocation(
            $filteredData,
            $promoter->id,
            $promoterCommissionDataLocationId
        );

        $promoterCommissionUpdatesData = $this->preparePromoterCommissionUpdateData($promoterCommissionUpdatesData);

        $lengthAwarePaginator = new LengthAwarePaginator(
            $promoterCommissionUpdatesData->forPage($filteredData['page'], $filteredData['per_page']),
            $promoterCommissionUpdatesData->count(),
            $filteredData['per_page'],
            $filteredData['page']
        );

        return [
            'commission_history' => PromoterCommissionHistoryListResource::collection(
                $lengthAwarePaginator->sortBy('happened_at')->values()
            ),
            'net_sales' => CommonFunctions::currencyFormat((float) $promoterCommissionUpdatesData->sum('net_sales')),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function getCommissionHistoryBySingleDate(Request $request): array
    {
        $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'selected_date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        $filterData = [
            'selected_date' => $request->get('selected_date'),
            'per_page' => $request->get('per_page'),
            'location_id' => $request->get('store_id') ?? $request->get('location_id'),
        ];

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->loadEmployee($promoter);

        /** @var Employee $employee */
        $employee = $promoter->employee;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($employee->company_id);

        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);
        $promoterCommissionData = $promoterCommissionUpdateQueries->getPromoterCommissionBySingleData(
            $filterData,
            $promoter->id
        );

        $promoterCommissionUpdate = $promoterCommissionData->getCollection();

        return [
            'summary' => [
                'date' => $filterData['selected_date'],
                'items_sold' => CommonFunctions::truncateDecimal($promoterCommissionUpdate->where(
                    'affected_by_type',
                    ModelMapping::SALE_ITEM->name
                )->sum('affected_by.quantity') ?? 0),
                'items_returned' => CommonFunctions::truncateDecimal($promoterCommissionUpdate->where(
                    'affected_by_type',
                    ModelMapping::SALE_RETURN_ITEM->name
                )->sum('affected_by.quantity') ?? 0),
                'commission_amount' => CommonFunctions::currencyFormat(
                    $promoterCommissionUpdate->sum('commission_amount'),
                    4
                ),
            ],
            'promoter_commission' => new PromoterCommissionListApiCollectionResource(
                $promoterCommissionData,
                $currency->getSymbol()
            ),
            'net_sales' => CommonFunctions::currencyFormat((float) $promoterCommissionData->sum('amount')),
            'total_records' => $promoterCommissionData->total(),
            'last_page' => $promoterCommissionData->lastPage(),
            'current_page' => $promoterCommissionData->currentPage(),
            'per_page' => $promoterCommissionData->perPage(),
        ];
    }

    public function getPromoterCommissionDetails(int $promoterCommissionUpdateId, Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->loadEmployee($promoter);

        /** @var Employee $employee */
        $employee = $promoter->employee;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($employee->company_id);

        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);
        $promoterCommissionDetails = $promoterCommissionUpdateQueries->fetchCommissionDetailsById(
            $promoterCommissionUpdateId
        );

        return [
            'details' => $promoterCommissionDetails ? new PromoterCommissionDetailsResource(
                $promoterCommissionDetails, $currency->getSymbol()
            ) : [],
        ];
    }

    private function preparePromoterCommissionUpdateData(Collection $promoterCommissionUpdatesData): Collection
    {
        $mergedResult = [];

        foreach ($promoterCommissionUpdatesData as $promoterCommissionUpdateData) {
            $happenedAt = $promoterCommissionUpdateData->happened_at;
            $saleReturnDate = $promoterCommissionUpdateData->sale_return_date;

            if (! isset($mergedResult[$happenedAt]) && null !== $happenedAt) {
                $mergedResult[$happenedAt] = [
                    'total_units_sold' => 0,
                    'total_sale_return_units_sold' => 0,
                    'happened_at' => $happenedAt,
                    'total_commission_amount' => 0,
                    'net_sales' => 0,
                ];
            }

            if (null !== $happenedAt) {
                $mergedResult[$happenedAt]['total_units_sold'] += $promoterCommissionUpdateData->units_sold;
                $mergedResult[$happenedAt]['total_commission_amount'] += $promoterCommissionUpdateData->commission_amount;
                $mergedResult[$happenedAt]['net_sales'] += $promoterCommissionUpdateData->net_sales;
            }

            if ($promoterCommissionUpdateData->units_returned && $saleReturnDate) {
                if (! isset($mergedResult[$saleReturnDate])) {
                    $mergedResult[$saleReturnDate] = [
                        'total_units_sold' => 0,
                        'total_sale_return_units_sold' => 0,
                        'happened_at' => $saleReturnDate,
                        'total_commission_amount' => 0,
                        'net_sales' => 0,
                    ];
                }

                $mergedResult[$saleReturnDate]['total_sale_return_units_sold'] += $promoterCommissionUpdateData->units_returned;
                $mergedResult[$saleReturnDate]['total_commission_amount'] += $promoterCommissionUpdateData->commission_amount;
                $mergedResult[$saleReturnDate]['net_sales'] += $promoterCommissionUpdateData->net_sales;
            }
        }

        return collect($mergedResult);
    }
}
