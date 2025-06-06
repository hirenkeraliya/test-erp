<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\Exports\SellThroughByDepartmentExport;
use App\Domains\SellThroughAggregate\Resources\SellThroughByDepartmentListResource;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellThroughByDepartmentServices
{
    public function fetchSellThroughDetailsByDepartment(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $departmentSalesData = $sellThroughAggregateQueries->sellThroughAggregateForDepartmentPaginate(
            $filterData,
            $companyId
        );
        $consolidateDepartmentSalesData = $sellThroughAggregateQueries->sellThroughAggregateForDepartmentGet(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $departmentSalesData->total(),
            'data' => SellThroughByDepartmentListResource::collection($departmentSalesData),
            'total' => [
                'received' => $consolidateDepartmentSalesData->sum('received'),
                'sold' => $consolidateDepartmentSalesData->sum('sold'),
                'online_sold' => $consolidateDepartmentSalesData->sum('online_sold'),
                'remaining' => $consolidateDepartmentSalesData->sum('balance'),
                'sell_through' => $this->getTotalSellThrough($consolidateDepartmentSalesData),
            ],
        ];
    }

    public function printSellThroughDetailsByDepartment(
        array $filterData,
        int $companyId,
        array $getFilterLabels
    ): string {
        $dataForCharts = $this->sellThroughDetailsByDepartmentForChart($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locations = null;

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        if ($filterData['location_ids']) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getLocationNamesWithCodesByIds($companyId, $filterData['location_ids']);
        }

        $consolidateDepartmentSalesData = $sellThroughAggregateQueries->sellThroughAggregateForDepartmentGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateDepartmentSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return view('prints.sell_through_by_department', [
            'sellThroughDataByDepartments' => SellThroughByDepartmentListResource::collection(
                $consolidateDepartmentSalesData
            )->jsonSerialize(),
            'sellThroughTotalDataByDepartments' => $totals,
            'chartRecords' => $dataForCharts['records'],
            'reportType' => Str::of(SellThroughTypes::DEPARTMENTS->name)->title()->value(),
            'filterDate' => $filterData['date'] ?? $filterData['date_range'],
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'locations' => $locations,
            'company' => $company,
            'columns' => $columns,
            'getFilterLabels' => $getFilterLabels,
            'colspan' => $colSpan,
        ])->render();
    }

    public function sellThroughDetailsByDepartmentForChart(array $filterData, int $companyId): array
    {
        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);

        $records = $sellThroughAggregateQueries->sellThroughAggregateForDepartmentGet($filterData, $companyId);

        $departmentRecords = $records->transform(function ($departmentData) {
            $received = (float) $departmentData->received;
            $sold = (float) $departmentData->sold;
            $onlineSold = (float) $departmentData->online_sold;
            $totalSold = $sold + $onlineSold;

            $departmentData->sell_through = 0.0 !== $received ? CommonFunctions::numberFormat(
                $totalSold * 100 / $received,
                2
            ) : 0;

            return $departmentData;
        })->where('sell_through', '>', 0);

        $totalReceived = (float) array_sum(array_slice($departmentRecords->pluck('received')->toArray(), 10));
        $regularSold = array_sum(array_slice($departmentRecords->pluck('sold')->toArray(), 10));
        $onlineSold = array_sum(array_slice($departmentRecords->pluck('online_sold')->toArray(), 10));
        $totalSold = $regularSold + $onlineSold;

        $sellThroughAfterTen = 0.0 !== $totalReceived ? CommonFunctions::numberFormat(
            $totalSold * 100 / $totalReceived
        ) : 0;

        $sellThroughServices = resolve(SellThroughServices::class);

        return [
            'records' => $sellThroughServices->getOnlyTenSellThrough(
                $departmentRecords->pluck('name')->toArray(),
                $departmentRecords->pluck('sell_through')->toArray(),
                $sellThroughAfterTen,
            ),
        ];
    }

    public function exportSellThroughDetailsByDepartment(
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

        $consolidateDepartmentSalesData = $sellThroughAggregateQueries->sellThroughAggregateForDepartmentGet(
            $filterData,
            $companyId
        );

        $sellThroughAggregateService = resolve(SellThroughAggregateService::class);
        [$columns, $totals, $colSpan] = $sellThroughAggregateService->getColumnTotalsAndColSpan(
            $consolidateDepartmentSalesData,
            collect(
                isset($filterData['export_columns']) && is_array(
                    $filterData['export_columns']
                ) ? $filterData['export_columns'] : []
            )
        );

        return Excel::download(
            new SellThroughByDepartmentExport(
                $consolidateDepartmentSalesData,
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

    public function fetchBalanceDetailsByDepartment(array $filterData, int $departmentId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $balanceDetails = $sellThroughAggregateQueries->balanceDetailsByDepartment(
            $filterData,
            $departmentId,
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

    public function fetchSoldDetailsByDepartment(array $filterData, int $departmentId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $soldDetails = $sellThroughAggregateQueries->soldDetailsByDepartment($filterData, $departmentId, $companyId);
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

    public function fetchReceivedDetailsByDepartment(array $filterData, int $departmentId, int $companyId): array
    {
        $sellThroughAggregateQueries = new SellThroughAggregateQueries();

        $receivedDetails = $sellThroughAggregateQueries->receivedDetailsByDepartment(
            $filterData,
            $departmentId,
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

    private function getTotalSellThrough(Collection $consolidateDepartmentSalesData): float
    {
        if ((float) $consolidateDepartmentSalesData->sum('received') === 0.0) {
            return 0;
        }

        return CommonFunctions::numberFormat(
            ($consolidateDepartmentSalesData->sum('sold') + $consolidateDepartmentSalesData->sum(
                'online_sold'
            )) * 100 / $consolidateDepartmentSalesData->sum('received')
        );
    }
}
