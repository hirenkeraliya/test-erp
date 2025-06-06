<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughByProductArticleNumberExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughByProductArticleNumberListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughProductArticleNumberServices
{
    public function fetchSellThroughDetailsByProductArticleNumber(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->sellThroughAggregateForArticleNumberPaginate(
            $filterData,
            $companyId
        );

        $consolidateProductSalesData = $sellThroughAggregateQueries->sellThroughAggregateForArticleNumberGet(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $productSalesData->total(),
            'data' => SellThroughByProductArticleNumberListResource::collection($productSalesData),
            'total' => $productSalesData->count() === 0 ? [] : [
                'received' => $consolidateProductSalesData->sum('received'),
                'sold' => $consolidateProductSalesData->sum('sold'),
                'online_sold' => $consolidateProductSalesData->sum('online_sold'),
                'remaining' => $consolidateProductSalesData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidateProductSalesData),
            ],
        ];
    }

    public function printSellThroughDetailsByProductArticleNumber(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsByProductArticleNumberForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $consolidateProductSalesData = $sellThroughAggregateQueries->sellThroughAggregateForArticleNumberGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateProductSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_product_article_number_or_upc', [
            'sellThroughDataByProducts' => SellThroughByProductArticleNumberListResource::collection(
                $consolidateProductSalesData
            )
            ->jsonSerialize(),
            'sellThroughTotalDataByProducts' => $totals,
            'reportType' => Str::of(SellThroughTypes::BY_MASTER_PRODUCT->name)->replace('_', ' ')->title()->value(),
            'chartRecords' => $dataForCharts['records'],
            'reportTypeByArticleNumber' => SellThroughTypes::BY_MASTER_PRODUCT->value,
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colSpan' => $colSpan,
        ])->render();
    }

    public function sellThroughDetailsByProductArticleNumberForChart(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $records = $sellThroughAggregateQueries->sellThroughAggregateForArticleNumberGet($filterData, $companyId);

        $productRecords = $records->transform(function ($productData) {
            $received = (float) $productData->received;
            $productData->sell_through = 0.0 !== $received ? CommonFunctions::numberFormat(
                $productData->sold * 100 / $received
            ) : 0;

            return $productData;
        })->where('sell_through', '>', 0);

        $totalReceived = (float) array_sum(array_slice($productRecords->pluck('received')->toArray(), 10));
        $totalSold = array_sum(array_slice($productRecords->pluck('sold')->toArray(), 10));

        $sellThroughAfterTen = 0.0 !== $totalReceived ? CommonFunctions::numberFormat(
            $totalSold * 100 / $totalReceived
        ) : 0;

        $sellThroughServices = resolve(SellThroughServices::class);

        return [
            'records' => $sellThroughServices->getOnlyTenSellThrough(
                $productRecords->pluck('article_number')->toArray(),
                $productRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
        ];
    }

    public function exportSellThroughDetailsByProductArticleNumber(
        array $filterData,
        int $companyId,
        string $filename,
        array $getFilterLabels
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currencyQueries->getByCompanyId($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $consolidateProductSalesData = $sellThroughAggregateQueries->sellThroughAggregateForArticleNumberGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateProductSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughByProductArticleNumberExport(
                collect(SellThroughByProductArticleNumberListResource::collection($consolidateProductSalesData)
            ->jsonSerialize()),
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

    public function fetchBalanceDetailsByArticleNumber(array $filterData, string $articleNumber, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );
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

    public function fetchSoldDetailsByArticleNumber(array $filterData, string $articleNumber, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsByArticleNumber(
            $filterData,
            $articleNumber,
            $companyId
        );
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

    public function fetchReceivedDetailsByArticleNumber(array $filterData, string $articleNumber, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsByArticleNumber(
            $filterData,
            $articleNumber,
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

    private function getTotalSellThrough(Collection $consolidateProductSalesData): float
    {
        if ((float) $consolidateProductSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateProductSalesData->sum('sold') + $consolidateProductSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateProductSalesData->sum('received')
        );
    }
}
