<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughBySizeExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughBySizeListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughBySizeServices
{
    public function fetchSellThroughDetailsBySize(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $sizeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForSizePaginate(
            $filterData,
            $companyId
        );

        $consolidateSizeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForSizeGet(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $sizeSalesData->total(),
            'data' => SellThroughBySizeListResource::collection($sizeSalesData),
            'total' => [
                'received' => $consolidateSizeSalesData->sum('received'),
                'sold' => $consolidateSizeSalesData->sum('sold'),
                'online_sold' => $consolidateSizeSalesData->sum('online_sold'),
                'remaining' => $consolidateSizeSalesData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidateSizeSalesData),
            ],
        ];
    }

    public function sellThroughDetailsBySizeForChart(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $records = $sellThroughAggregateQueries->sellThroughAggregateForSizeGet($filterData, $companyId);

        // Update to include online_sold
        $sizeRecords = $records->map(function ($sizeRecord) {
            $received = (float) $sizeRecord->received;
            $sold = (float) $sizeRecord->sold;
            $onlineSold = (float) $sizeRecord->online_sold;
            $totalSold = $sold + $onlineSold;

            $sizeRecord->sell_through = 0.0 !== $received ? CommonFunctions::numberFormat(
                $totalSold * 100 / $received,
                2
            ) : 0;

            return $sizeRecord;
        })->where('sell_through', '>', 0);

        $totalReceived = (float) array_sum(array_slice($sizeRecords->pluck('received')->toArray(), 10));
        $regularSold = array_sum(array_slice($sizeRecords->pluck('sold')->toArray(), 10));
        $onlineSold = array_sum(array_slice($sizeRecords->pluck('online_sold')->toArray(), 10));
        $totalSold = $regularSold + $onlineSold;

        $sellThroughAfterTen = 0.0 !== $totalReceived ? CommonFunctions::numberFormat(
            $totalSold * 100 / $totalReceived,
            2
        ) : 0;

        $saleThroughServices = resolve(SellThroughServices::class);

        return [
            'records' => $saleThroughServices->getOnlyTenSellThrough(
                $sizeRecords->pluck('name')->toArray(),
                $sizeRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen
            ),
        ];
    }

    public function printSellThroughDetailsBySize(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsBySizeForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        if ($filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $consolidateSizeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForSizeGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateSizeSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_size', [
            'sellThroughDataBySizes' => SellThroughBySizeListResource::collection(
                $consolidateSizeSalesData
            )->jsonSerialize(),
            'sellThroughTotalDataBySizes' => $totals,
            'chartRecords' => $dataForCharts['records'],
            'reportType' => Str::of(SellThroughTypes::SIZES->name)->title()->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colspan' => $colSpan,
        ])->render();
    }

    public function exportSellThroughDetailsBySize(
        array $filterData,
        int $companyId,
        string $filename,
        array $getFilterLabels
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if ($filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $consolidateSizeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForSizeGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateSizeSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughBySizeExport(
                $consolidateSizeSalesData,
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
                $colSpan
            ),
            $filename
        );
    }

    public function fetchBalanceDetailsBySize(array $filterData, int $sizeId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsBySize($filterData, $sizeId, $companyId);
        $data = [];

        $totals = [
            'balance' => 0,
        ];

        foreach ($balanceDetails as $balanceDetail) {
            $data[] = [
                'location_name' => $balanceDetail->location->getNameWithType(),
                'balance' => $balanceDetail->balance,
            ];

            $totals['balance'] += $balanceDetail->balance;
        }

        return [
            'data' => $data,
            'totals' => $totals,
        ];
    }

    public function fetchSoldDetailsBySize(array $filterData, int $sizeId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsBySize($filterData, $sizeId, $companyId);
        $data = [];

        $totals = [
            'sold' => 0,
            'foc_sold' => 0,
            'return' => 0,
        ];

        foreach ($soldDetails as $soldDetail) {
            $data[] = [
                'location_name' => $soldDetail->location->getNameWithType(),
                'sold' => $soldDetail->sold,
                'foc_sold' => $soldDetail->foc_sold,
                'return' => $soldDetail->return_quantity,
            ];

            $totals['sold'] += $soldDetail->sold;
            $totals['foc_sold'] += $soldDetail->foc_sold;
            $totals['return'] += $soldDetail->return_quantity;
        }

        return [
            'data' => $data,
            'totals' => $totals,
        ];
    }

    public function fetchReceivedDetailsBySize(array $filterData, int $sizeId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsBySize($filterData, $sizeId, $companyId);
        $data = [];

        $totals = [
            'goods_receive_note_in_balance' => 0,
            'goods_receive_note_out_balance' => 0,
            'stock_adjustment_in_balance' => 0,
            'stock_adjustment_out_balance' => 0,
            'stock_transfer_in_balance' => 0,
            'stock_transfer_out_balance' => 0,
            'delivery_order_in_balance' => 0,
            'delivery_order_out_balance' => 0,
        ];

        foreach ($receivedDetails as $receivedDetail) {
            $data[] = [
                'location_name' => $receivedDetail->location->getNameWithType(),
                'goods_receive_note_in_balance' => $receivedDetail->goods_receive_note_in_balance,
                'goods_receive_note_out_balance' => $receivedDetail->goods_receive_note_out_balance,
                'stock_adjustment_in_balance' => $receivedDetail->stock_adjustment_in_balance,
                'stock_adjustment_out_balance' => $receivedDetail->stock_adjustment_out_balance,
                'stock_transfer_in_balance' => $receivedDetail->stock_transfer_in_balance,
                'stock_transfer_out_balance' => $receivedDetail->stock_transfer_out_balance,
                'delivery_order_in_balance' => $receivedDetail->delivery_order_in_balance,
                'delivery_order_out_balance' => $receivedDetail->delivery_order_out_balance,
            ];

            $totals['goods_receive_note_in_balance'] += $receivedDetail->goods_receive_note_in_balance;
            $totals['goods_receive_note_out_balance'] += $receivedDetail->goods_receive_note_out_balance;
            $totals['stock_adjustment_in_balance'] += $receivedDetail->stock_adjustment_in_balance;
            $totals['stock_adjustment_out_balance'] += $receivedDetail->stock_adjustment_out_balance;
            $totals['stock_transfer_in_balance'] += $receivedDetail->stock_transfer_in_balance;
            $totals['stock_transfer_out_balance'] += $receivedDetail->stock_transfer_out_balance;
            $totals['delivery_order_in_balance'] += $receivedDetail->delivery_order_in_balance;
            $totals['delivery_order_out_balance'] += $receivedDetail->delivery_order_out_balance;
        }

        return [
            'data' => $data,
            'totals' => $totals,
        ];
    }

    private function getTotalSellThrough(Collection $consolidateSizeSalesData): float
    {
        if ((float) $consolidateSizeSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateSizeSalesData->sum('sold') + $consolidateSizeSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateSizeSalesData->sum('received')
        );
    }
}
