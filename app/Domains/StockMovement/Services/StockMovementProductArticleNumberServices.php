<?php

declare(strict_types=1);

namespace App\Domains\StockMovement\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockMovement\Exports\StockMovementByProductArticleNumberExport;
use App\Domains\StockMovement\Resources\StockMovementByProductArticleNumberListResource;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementProductArticleNumberServices
{
    public function fetchStockMovementDetailsByProductArticleNumber(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->stockMovementForArticleNumber($filterData, $companyId);

        $lengthAwarePaginator = new LengthAwarePaginator(
            $productSalesData->forPage($filterData['page'], $filterData['per_page']),
            $productSalesData->count(),
            $filterData['per_page'],
            $filterData['page']
        );

        $stockMovementService = resolve(StockMovementServices::class);

        return [
            'data' => StockMovementByProductArticleNumberListResource::collection($lengthAwarePaginator->values()),
            'total' => $stockMovementService->getBadgesTotal($productSalesData),
            'total_records' => $lengthAwarePaginator->total(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'current_page' => $lengthAwarePaginator->currentPage(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }

    public function printStockMovementDetailsByProductArticleNumber(
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

        $productSalesData = $sellThroughAggregateQueries->stockMovementForArticleNumber($filterData, $companyId);

        $columns = $this->columnHeading();

        $totals = [
            'sold' => $productSalesData->sum('sold'),
            'remaining' => $productSalesData->sum('balance'),
        ];

        return view('prints.stock_movement_by_product_article_number_or_upc', [
            'stockMovementDataByProducts' => StockMovementByProductArticleNumberListResource::collection(
                $productSalesData
            ),
            'stockMovementTotalDataByProducts' => $totals,
            'reportType' => Str::of(SellThroughTypes::BY_MASTER_PRODUCT->name)->replace('_', ' ')->title()->value(),
            'reportTypeByArticleNumber' => SellThroughTypes::BY_MASTER_PRODUCT->value,
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colSpan' => 12,
        ])->render();
    }

    public function exportStockMovementDetailsByProductArticleNumber(
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

        $productSalesData = $sellThroughAggregateQueries->stockMovementForArticleNumber($filterData, $companyId);

        $columns = $this->columnHeading();

        $totals = [
            'sold' => $productSalesData->sum('sold'),
            'remaining' => $productSalesData->sum('balance'),
        ];

        return Excel::download(
            new StockMovementByProductArticleNumberExport(
                $productSalesData,
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
                $colSpan = 12
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
            'Article Number',
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
