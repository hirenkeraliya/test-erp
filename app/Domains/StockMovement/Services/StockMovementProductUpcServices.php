<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockMovement\Exports\StockMovementByProductUpcExport;
use App\Domains\StockMovement\Resources\StockMovementByProductUpcListResource;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementProductUpcServices
{
    public function fetchStockMovementDetailsByProductUpc(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->stockMovementAggregateForUpc($filterData, $companyId);

        $lengthAwarePaginator = new LengthAwarePaginator(
            $productSalesData->forPage($filterData['page'], $filterData['per_page']),
            $productSalesData->count(),
            $filterData['per_page'],
            $filterData['page']
        );

        $stockMovementService = resolve(StockMovementServices::class);

        return [
            'data' => StockMovementByProductUpcListResource::collection($lengthAwarePaginator->values()),
            'total' => $stockMovementService->getBadgesTotal($productSalesData),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function printStockMovementDetailsByProductUpc(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->stockMovementAggregateForUpc($filterData, $companyId);

        $sold = 0;
        $remaining = 0;

        $sold = $productSalesData->sum('sold');
        $remaining = $productSalesData->sum('balance');

        $columns = $this->columnHeading();

        $totals = [
            'sold' => $sold,
            'remaining' => $remaining,
        ];

        return view('prints.stock_movement_by_product_article_number_or_upc', [
            'stockMovementDataByProducts' => StockMovementByProductUpcListResource::collection(
                $productSalesData
            )->jsonSerialize(),
            'stockMovementTotalDataByProducts' => $totals,
            'reportType' => Str::of(SellThroughTypes::BY_UPC->name)->title()->replace('_', ' ')->value(),
            'reportTypeByUpc' => SellThroughTypes::BY_UPC->value,
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colSpan' => 14,
        ])->render();
    }

    public function exportStockMovementDetailsByProductUpc(
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

        $productSalesData = $sellThroughAggregateQueries->stockMovementAggregateForUpc($filterData, $companyId);

        $sold = 0;
        $remaining = 0;

        $sold = $productSalesData->sum('sold');
        $remaining = $productSalesData->sum('balance');

        $columns = $this->columnHeading();

        $totals = [
            'sold' => $sold,
            'remaining' => $remaining,
        ];

        return Excel::download(
            new StockMovementByProductUpcExport(
                $productSalesData,
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
                $colSpan = 14
            ),
            $filename
        );
    }

    private function columnHeading(): array
    {
        return [
            'Name',
            'Location',
            'Price',
            'Upc',
            'Color',
            'Size',
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
