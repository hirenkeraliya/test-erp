<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockMovement\Exports\StockMovementByStoreExport;
use App\Domains\StockMovement\Resources\StockMovementByStoreListResource;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementLocationServices
{
    public function fetchStockMovementDetailsByStore(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $locationSalesData = $sellThroughAggregateQueries->stockMovementForLocation($filterData, $companyId);

        $lengthAwarePaginator = new LengthAwarePaginator(
            $locationSalesData->forPage($filterData['page'], $filterData['per_page']),
            $locationSalesData->count(),
            $filterData['per_page'],
            $filterData['page']
        );

        $stockMovementService = resolve(StockMovementServices::class);

        return [
            'data' => StockMovementByStoreListResource::collection($lengthAwarePaginator->values()),
            'total' => $stockMovementService->getBadgesTotal($locationSalesData),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function printStockMovementDetailsByStore(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        if (null !== $filterData['location_ids']) {
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $locationSalesData = $sellThroughAggregateQueries->stockMovementForLocation($filterData, $companyId);

        $columns = $this->columnHeading();

        $totals = [
            'sold' => $locationSalesData->sum('sold'),
            'remaining' => $locationSalesData->sum('balance'),
        ];

        return view('prints.stock_movement_by_location', [
            'stockMovementDataByLocations' => StockMovementByStoreListResource::collection($locationSalesData),
            'stockMovementTotalDataByLocations' => $totals,
            'reportType' => Str::of(SellThroughTypes::LOCATIONS->name)->title()->replace('_', ' ')->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
        ])->render();
    }

    public function exportStockMovementDetailsByStore(
        array $filterData,
        int $companyId,
        string $filename,
        array $getFilterLabels
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        if (null !== $filterData['location_ids']) {
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $locationSalesData = $sellThroughAggregateQueries->stockMovementForLocation($filterData, $companyId);

        $columns = $this->columnHeading();

        $totals = [
            'sold' => $locationSalesData->sum('sold'),
            'remaining' => $locationSalesData->sum('balance'),
        ];

        return Excel::download(
            new StockMovementByStoreExport(
                $locationSalesData,
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
            'Code',
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
