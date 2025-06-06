<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Exports\CreditSalesBySummaryExport;
use App\Domains\Sale\SaleQueries;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditSaleCustomReportBySummaryService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        [$creditSalesData, $grandTotal, $columns] = $this->prepareReport($filterData, $companyId);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.credit_sales_by_summary', [
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'creditSalesData' => $creditSalesData,
            'columns' => $columns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'grandTotal' => $grandTotal,
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        [$creditSalesData, $grandTotal, $columns] = $this->prepareReport($filterData, $companyId);

        $customReportService = resolve(CustomReportService::class);

        return Excel::download(
            new CreditSalesBySummaryExport(
                $company,
                $creditSalesData,
                $grandTotal,
                $columns,
                $customReportService->prepareDateRange($filterData)
            ),
            $filename
        );
    }

    private function prepareReport(array $filterData, int $companyId): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getCreditSalesWithItemsData($filterData, $companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($companyId, $filterData['location_ids']);

        $locationsSales = [];
        $grandTotal = [
            'total_credit_pending_amount' => 0,
            'total_amount_paid' => 0,
            'total_amount' => 0,
        ];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'products' => [],
                'totals' => [
                    'total_credit_pending_amount' => 0,
                    'total_amount_paid' => 0,
                    'total_amount' => 0,
                ],
            ];

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($sales->groupBy('counterUpdate.counter.location_id') as $locationId => $sales) {
                if ($locationId === $location->id) {
                    $locationSales['totals']['total_amount_paid'] += $sales->sum('total_amount_paid');
                    $locationSales['totals']['total_credit_pending_amount'] += $sales->sum('credit_pending_amount');
                    $locationSales['totals']['total_amount'] += $sales->sum('total_amount_paid') + $sales->sum(
                        'credit_pending_amount'
                    );
                    $grandTotal['total_amount_paid'] += $sales->sum('total_amount_paid');
                    $grandTotal['total_credit_pending_amount'] += $sales->sum('credit_pending_amount');
                    $grandTotal['total_amount'] += $sales->sum('total_amount_paid') + $sales->sum(
                        'credit_pending_amount'
                    );

                    foreach ($sales as $sale) {
                        $counterUpdate = $sale->counterUpdate;
                        $counter = $counterUpdate->counter;
                        $cashier = $counterUpdate->cashier;
                        $cashierEmployee = $cashier->employee;
                        $creditAuthorizer = $sale->creditAuthorizer;
                        $creditAuthorizerEmployee = $creditAuthorizer->employee;

                        $locationSales['products'][] = [
                            'receipt_number' => $sale->offline_sale_id,
                            'counter' => $counter->name,
                            'cashier' => $cashierEmployee->getFullName(),
                            'total_amount_paid' => $sale->total_amount_paid,
                            'credit_pending_amount' => $sale->credit_pending_amount,
                            'credit_authorizer' => $creditAuthorizerEmployee->getFullName() . '(' . ModelMapping::getFormattedCaseName(
                                $creditAuthorizer::class
                            ) . ')',
                            'total_amount' => $sale->total_amount_paid + $sale->credit_pending_amount,
                            'status' => SaleStatus::getFormattedCaseName($sale->getStatus()),
                        ];
                    }
                }
            }

            $locationsSales[] = $locationSales;
            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $columns = ['Receipt Number', 'Status', 'Counter', 'Cashier', 'Authorizer', 'Amount', 'Paid', 'Due'];

        return [$locationsSales, $grandTotal, $columns];
    }
}
