<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughByProductUpcExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughByProductUpcListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughProductUpcServices
{
    public function fetchSellThroughDetailsByProductUpc(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->sellThroughAggregateForUpcPaginate(
            $filterData,
            $companyId
        );

        $consolidateProductSalesData = $sellThroughAggregateQueries->sellThroughAggregateForUpcGet(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $productSalesData->count(),
            'data' => SellThroughByProductUpcListResource::collection($productSalesData),
            'total' => $consolidateProductSalesData->count() === 0 ? [] : [
                'received' => $consolidateProductSalesData->sum('received'),
                'sold' => $consolidateProductSalesData->sum('sold'),
                'online_sold' => $consolidateProductSalesData->sum('online_sold'),
                'remaining' => $consolidateProductSalesData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidateProductSalesData),
            ],
        ];
    }

    public function printSellThroughDetailsByProductUpc(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsByProductUpcForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        if (null !== $filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->sellThroughAggregateForUpcGet($filterData, $companyId);

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $productSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_product_article_number_or_upc', [
            'sellThroughDataByProducts' => SellThroughByProductUpcListResource::collection(
                $productSalesData
            )->jsonSerialize(),
            'sellThroughTotalDataByProducts' => $totals,
            'reportType' => Str::of(SellThroughTypes::BY_UPC->name)->title()->replace('_', ' ')->value(),
            'reportTypeByUpc' => SellThroughTypes::BY_UPC->value,
            'chartRecords' => $dataForCharts['records_for_bar'],
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colSpan' => $colSpan,
        ])->render();
    }

    public function sellThroughDetailsByProductUpcForChart(array $filterData, int $companyId): array
    {
        $isProductVariant = config('app.product_variant');
        $productService = resolve(ProductService::class);

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $productSalesData = $sellThroughAggregateQueries->sellThroughAggregateForUpcGet($filterData, $companyId);

        $productRecords = $productSalesData->map(function ($productData) use ($isProductVariant, $productService) {
            if ($isProductVariant) {
                $attributes = $productService->getAttributesValueForPrint($productData->product);
                $productData->name .= $attributes;
            } else {
                $colorName = $productData->color_name;
                $sizeName = $productData->size_name;
                $productData->name = $productData->name . $colorName . $sizeName;
            }

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
                $productRecords->pluck('upc')->toArray(),
                $productRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
            'records_for_bar' => $sellThroughServices->getOnlyTenSellThroughUPCForColor(
                $productRecords,
                $sellThroughAfterTen,
            ),
        ];
    }

    public function exportSellThroughDetailsByProductUpc(
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

        $productSalesData = $sellThroughAggregateQueries->sellThroughAggregateForUpcGet($filterData, $companyId);

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $productSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughByProductUpcExport(
                $productSalesData,
                $totals,
                $company,
                $locations,
                $filterData['date'] ?? $filterData['date_range'],
                $columns,
                $getFilterLabels,
                $colSpan = $colSpan,
            ),
            $filename
        );
    }

    public function fetchBalanceDetailsByUpc(array $filterData, int $productId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsByUpc($filterData, $productId, $companyId);
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

    public function fetchSoldDetailsByUpc(array $filterData, int $productId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsByUpc($filterData, $productId, $companyId);
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

    public function fetchReceivedDetailsByUpc(array $filterData, int $productId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsByUpc($filterData, $productId, $companyId);
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
