<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockMovement\Exports\StockMovementByStyleExport;
use App\Domains\StockMovement\Resources\StockMovementByStyleListResource;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementByStyleServices
{
    public function fetchStockMovementDetailsByStyle(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $styleSalesData = $sellThroughAggregateQueries->stockMovementSummaryForStyle($filterData, $companyId);

        $lengthAwarePaginator = new LengthAwarePaginator(
            $styleSalesData->forPage($filterData['page'], $filterData['per_page']),
            $styleSalesData->count(),
            $filterData['per_page'],
            $filterData['page']
        );
        $stockMovementService = resolve(StockMovementServices::class);

        return [
            'data' => StockMovementByStyleListResource::collection($lengthAwarePaginator),
            'total' => $stockMovementService->getBadgesTotal($styleSalesData),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function printStockMovementDetailsByStyle(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        if ($filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $styleSalesData = $sellThroughAggregateQueries->stockMovementSummaryForStyle($filterData, $companyId);

        $columns = $this->columnHeading();

        return view('prints.stock_movement_by_style', [
            'stockMovementDataByStyles' => StockMovementByStyleListResource::collection($styleSalesData),
            'stockMovementTotalDataByStyles' => [
                'name' => 'Grand Total',
                'sold' => $styleSalesData->sum('sold'),
                'remaining' => $styleSalesData->sum('balance'),
            ],
            'reportType' => Str::of(SellThroughTypes::STYLES->name)->title()->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
        ])->render();
    }

    public function exportStockMovementDetailsByStyle(
        array $filterData,
        int $companyId,
        string $filename,
        array $getFilterLabels
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $styleSalesData = $sellThroughAggregateQueries->stockMovementSummaryForStyle($filterData, $companyId);

        $totals = [
            'name' => 'Grand Total',
            'sold' => $styleSalesData->sum('sold'),
            'remaining' => $styleSalesData->sum('balance'),
        ];

        $columns = $this->columnHeading();

        return Excel::download(
            new StockMovementByStyleExport(
                $styleSalesData,
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
            ),
            $filename
        );
    }

    private function columnHeading(): array
    {
        return [
            'Name',
            'Location',
            'GRN In',
            'GRN Out',
            'Adjustment In',
            'Adjustment Out',
            'Transfer In',
            'Transfer Out',
            'Delivery In',
            'Delivery Out',
            'Sold',
            'Balance',
        ];
    }
}
