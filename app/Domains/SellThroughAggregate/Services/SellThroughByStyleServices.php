<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughByStyleExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughByStyleListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughByStyleServices
{
    public function fetchSellThroughDetailsByStyle(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $styleSalesData = $sellThroughAggregateQueries->sellThroughAggregateForStylePaginate(
            $filterData,
            $companyId
        );

        $consolidateStyleSalesData = $sellThroughAggregateQueries->sellThroughAggregateForStyleGet(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $styleSalesData->total(),
            'data' => SellThroughByStyleListResource::collection($styleSalesData),
            'total' => [
                'received' => $consolidateStyleSalesData->sum('received'),
                'sold' => $consolidateStyleSalesData->sum('sold'),
                'online_sold' => $consolidateStyleSalesData->sum('online_sold'),
                'remaining' => $consolidateStyleSalesData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidateStyleSalesData),
            ],
        ];
    }

    public function printSellThroughDetailsByStyle(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsByStyleForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        if ($filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $consolidateStyleSalesData = $sellThroughAggregateQueries->sellThroughAggregateForStyleGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateStyleSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_style', [
            'sellThroughDataByStyles' => SellThroughByStyleListResource::collection(
                $consolidateStyleSalesData
            )->jsonSerialize(),
            'sellThroughTotalDataByStyles' => $totals,
            'chartRecords' => $dataForCharts['records'],
            'reportType' => Str::of(SellThroughTypes::STYLES->name)->title()->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colspan' => $colSpan,
        ])->render();
    }

    public function sellThroughDetailsByStyleForChart(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $records = $sellThroughAggregateQueries->sellThroughAggregateForStyleGet($filterData, $companyId);

        $styleRecords = $records->transform(function ($styleData) {
            $received = (float) $styleData->received;
            $styleData->sell_through = 0.0 !== $received ? CommonFunctions::numberFormat(
                ($styleData->sold + $styleData->online_sold) * 100 / $received
            ) : 0;

            return $styleData;
        })->where('sell_through', '>', 0);

        $totalReceived = (float) array_sum(array_slice($styleRecords->pluck('received')->toArray(), 10));
        $regularSold = array_sum(array_slice($styleRecords->pluck('sold')->toArray(), 10));
        $onlineSold = array_sum(array_slice($styleRecords->pluck('online_sold')->toArray(), 10));
        $totalSold = $regularSold + $onlineSold;

        $sellThroughAfterTen = 0.0 !== $totalReceived ? CommonFunctions::numberFormat(
            $totalSold * 100 / $totalReceived,
            2
        ) : 0;

        $sellThroughServices = resolve(SellThroughServices::class);

        return [
            'records' => $sellThroughServices->getOnlyTenSellThrough(
                $styleRecords->pluck('name')->toArray(),
                $styleRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
        ];
    }

    public function exportSellThroughDetailsByStyle(
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

        $consolidateStyleSalesData = $sellThroughAggregateQueries->sellThroughAggregateForStyleGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateStyleSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughByStyleExport(
                $consolidateStyleSalesData,
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

    public function fetchBalanceDetailsByStyle(array $filterData, int $styleId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsByStyle($filterData, $styleId, $companyId);
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

    public function fetchSoldDetailsByStyle(array $filterData, int $styleId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsByStyle($filterData, $styleId, $companyId);
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

    public function fetchReceivedDetailsByStyle(array $filterData, int $styleId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsByStyle($filterData, $styleId, $companyId);
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

    private function getTotalSellThrough(Collection $consolidateStyleSalesData): float
    {
        if ((float) $consolidateStyleSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateStyleSalesData->sum('sold') + $consolidateStyleSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateStyleSalesData->sum('received')
        );
    }
}
