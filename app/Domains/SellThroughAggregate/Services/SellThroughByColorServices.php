<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughByColorExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughByColorListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughByColorServices
{
    public function fetchSellThroughDetailsByColor(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $sellThroughData = $sellThroughAggregateQueries->sellThroughAggregateForColorPaginate(
            $filterData,
            $companyId
        );

        $consolidate = $sellThroughAggregateQueries->sellThroughAggregateForColorGet($filterData, $companyId);

        return [
            'total_records' => $sellThroughData->total(),
            'data' => SellThroughByColorListResource::collection($sellThroughData),
            'total' => [
                'received' => $sellThroughData->sum('received'),
                'sold' => $sellThroughData->sum('sold'),
                'online_sold' => $sellThroughData->sum('online_sold'),
                'remaining' => $sellThroughData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidate),
            ],
        ];
    }

    public function printSellThroughDetailsByColor(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsByColorForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $consolidateColorSalesData = $sellThroughAggregateQueries->sellThroughAggregateForColorGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateColorSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_color', [
            'sellThroughDataByColors' => SellThroughByColorListResource::collection(
                $consolidateColorSalesData
            )->jsonSerialize(),
            'sellThroughTotalDataByColors' => $totals,
            'chartRecords' => $dataForCharts['records'],
            'chartRecordsPieColors' => $dataForCharts['colors'],
            'chartRecordForBars' => $dataForCharts['records_for_bar'],
            'reportType' => Str::of(SellThroughTypes::COLORS->name)->title()->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colspan' => $colSpan,
        ])->render();
    }

    public function sellThroughDetailsByColorForChart(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $consolidateColorSalesData = $sellThroughAggregateQueries->sellThroughAggregateForColorGet(
            $filterData,
            $companyId
        );

        $colorRecords = $consolidateColorSalesData->transform(function ($colorRecord) {
            $received = (float) $colorRecord->received;
            $colorRecord->sell_through = 0.0 !== $received ? CommonFunctions::numberFormat(
                ($colorRecord->sold + $colorRecord->online_sold) * 100 / $received
            ) : 0;

            return $colorRecord;
        })->where('sell_through', '>', 0);

        $totalReceived = (float) array_sum(array_slice($colorRecords->pluck('received')->toArray(), 10));
        $regularSold = array_sum(array_slice($colorRecords->pluck('sold')->toArray(), 10));
        $onlineSold = array_sum(array_slice($colorRecords->pluck('online_sold')->toArray(), 10));
        $totalSold = $regularSold + $onlineSold;

        $sellThroughAfterTen = 0.0 !== $totalReceived ? CommonFunctions::numberFormat(
            $totalSold * 100 / $totalReceived,
            2
        ) : 0;

        $sellThroughServices = resolve(SellThroughServices::class);

        return [
            'records' => $sellThroughServices->getOnlyTenSellThrough(
                $colorRecords->pluck('name')->toArray(),
                $colorRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
            'colors' => $sellThroughServices->getColorNames($colorRecords->pluck('name')),
            'records_for_bar' => $sellThroughServices->getOnlyTenSellThroughForColor(
                $colorRecords->pluck('name')->toArray(),
                $colorRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
        ];
    }

    public function exportSellThroughDetailsByColor(
        array $filterData,
        int $companyId,
        string $filename,
        array $getFilterLabels,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $consolidateColorSalesData = $sellThroughAggregateQueries->sellThroughAggregateForColorGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateColorSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughByColorExport(
                SellThroughByColorListResource::collection($consolidateColorSalesData)->jsonSerialize(),
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
                $colSpan,
            ),
            $filename
        );
    }

    public function fetchBalanceDetailsByColor(array $filterData, int $colorId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsByColor($filterData, $colorId, $companyId);
        $data = [];

        $totals = [
            'balance' => 0,
        ];

        foreach ($balanceDetails as $balanceDetail) {
            $data[] = [
                'location_name' => $balanceDetail->location->getNameWithType(),
                'balance' => $balanceDetail->balance,
            ];
        }

        return [
            'data' => $data,
            'totals' => $totals,
        ];
    }

    public function fetchSoldDetailsByColor(array $filterData, int $colorId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsByColor($filterData, $colorId, $companyId);
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

    public function fetchReceivedDetailsByColor(array $filterData, int $colorId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsByColor($filterData, $colorId, $companyId);
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

    private function getTotalSellThrough(Collection $consolidateColorSalesData): float
    {
        if ((float) $consolidateColorSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateColorSalesData->sum('sold') + $consolidateColorSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateColorSalesData->sum('received')
        );
    }
}
