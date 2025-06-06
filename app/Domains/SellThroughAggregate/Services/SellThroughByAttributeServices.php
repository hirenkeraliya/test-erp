<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughByAttributeExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughByAttributeListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughByAttributeServices
{
    public function fetchSellThroughDetailsByAttribute(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $attributeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForAttributePaginate(
            $filterData,
            $companyId
        );

        $consolidateAttributeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForAttributeGet(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $attributeSalesData->total(),
            'data' => SellThroughByAttributeListResource::collection($attributeSalesData),
            'total' => [
                'received' => $consolidateAttributeSalesData->sum('received'),
                'sold' => $consolidateAttributeSalesData->sum('sold'),
                'online_sold' => $consolidateAttributeSalesData->sum('online_sold'),
                'remaining' => $consolidateAttributeSalesData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidateAttributeSalesData),
            ],
        ];
    }

    private function getTotalSellThrough(Collection $consolidateAttributeSalesData): float
    {
        if ((float) $consolidateAttributeSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateAttributeSalesData->sum('sold') + $consolidateAttributeSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateAttributeSalesData->sum('received')
        );
    }

    public function printSellThroughDetailsByAttribute(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsByAttributeForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        if ($filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $consolidateAttributeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForAttributeGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colspan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateAttributeSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_attribute', [
            'sellThroughDataByAttributes' => SellThroughByAttributeListResource::collection(
                $consolidateAttributeSalesData
            )->jsonSerialize(),
            'sellThroughTotalDataByAttributes' => $totals,
            'chartRecords' => $dataForCharts['records'],
            'reportType' => Str::of(SellThroughTypes::BY_ATTRIBUTES->name)->replace('_', ' ')->title()->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colspan' => $colspan,
        ])->render();
    }

    public function sellThroughDetailsByAttributeForChart(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $records = $sellThroughAggregateQueries->sellThroughAggregateForAttributeGet($filterData, $companyId);

        $attributeRecords = $records->map(function ($attributeRecord) {
            $received = (float) $attributeRecord->received;
            $sold = (float) $attributeRecord->sold;
            $onlineSold = (float) $attributeRecord->online_sold;
            $totalSold = $sold + $onlineSold;

            $attributeRecord->sell_through = 0.0 !== $received ? CommonFunctions::numberFormat(
                $totalSold * 100 / $received,
                2
            ) : 0;

            return $attributeRecord;
        })->where('sell_through', '>', 0);

        $totalReceived = (float) array_sum(array_slice($attributeRecords->pluck('received')->toArray(), 10));
        $regularSold = array_sum(array_slice($attributeRecords->pluck('sold')->toArray(), 10));
        $onlineSold = array_sum(array_slice($attributeRecords->pluck('online_sold')->toArray(), 10));
        $totalSold = $regularSold + $onlineSold;

        $sellThroughAfterTen = 0.0 !== $totalReceived ? CommonFunctions::numberFormat(
            $totalSold * 100 / $totalReceived,
            2
        ) : 0;

        $saleThroughServices = resolve(SellThroughServices::class);

        return [
            'records' => $saleThroughServices->getOnlyTenSellThrough(
                $attributeRecords->pluck('name')->toArray(),
                $attributeRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
        ];
    }

    public function exportSellThroughDetailsByAttribute(
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

        $consolidateAttributeSalesData = $sellThroughAggregateQueries->sellThroughAggregateForAttributeGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colspan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateAttributeSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughByAttributeExport(
                $consolidateAttributeSalesData,
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
                $colspan
            ),
            $filename
        );
    }

    public function fetchBalanceDetailsByAttribute(array $filterData, string $attribute, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsByAttribute($filterData, $attribute, $companyId);
        $data = [];

        $totals = [
            'balance' => 0,
        ];

        foreach ($balanceDetails as $balanceDetail) {
            $data[] = [
                'location_name' => $balanceDetail->location->name,
                'balance' => $balanceDetail->balance,
            ];

            $totals['balance'] += $balanceDetail->balance;
        }

        return [
            'data' => $data,
            'totals' => $totals,
        ];
    }

    public function fetchSoldDetailsByAttribute(array $filterData, string $attribute, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsByAttribute($filterData, $attribute, $companyId);
        $data = [];

        $totals = [
            'sold' => 0,
            'foc_sold' => 0,
            'return' => 0,
        ];

        foreach ($soldDetails as $soldDetail) {
            $data[] = [
                'location_name' => $soldDetail->location->name,
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

    public function fetchReceivedDetailsByAttribute(array $filterData, string $attribute, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsByAttribute(
            $filterData,
            $attribute,
            $companyId
        );
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
                'location_name' => $receivedDetail->location->name,
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
}
