<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SaleDiscountTypeReports;
use App\Domains\Sale\Exports\SaleDiscountReportExport;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SalePriceOverride;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleDiscountCustomReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $sales = $saleDiscountQueries->getSaleDiscounts($filterData);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        [$saleDiscounts, $columns, $dateRange] = $this->preparedByDocument($sales, $filterData, $locations);

        return view('prints.sale_discount_report', [
            'saleDiscounts' => $saleDiscounts,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'reportType' => SaleDiscountTypeReports::getFormattedCaseName((int) $filterData['report_type']),
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleDiscounts = $saleDiscountQueries->getSaleDiscounts($filterData);

        [$saleDiscounts, $columns, $dateRange] = $this->preparedByDocument($saleDiscounts, $filterData, $locations);

        $reportType = $filterData['report_type'] ? SaleDiscountTypeReports::getFormattedCaseName(
            (int) $filterData['report_type']
        ) : '';

        return Excel::download(
            new SaleDiscountReportExport($saleDiscounts, $dateRange, $company, $columns, $reportType),
            $filename
        );
    }

    public function preparedByDocument(Collection $saleDiscounts, array $filterData, Collection $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationsSales = [];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales_data' => [],
            ];

            $totalCartDiscount = 0;
            $totalCartAmount = 0;
            $totalNetSales = 0;
            $totalVariation = 0;

            foreach ($saleDiscounts->where('sale.counterUpdate.counter.location_id', $location->id)->sortBy(
                'sale.happened_at'
            ) as $saleDiscount) {
                /** @var Sale $sale */
                $sale = $saleDiscount->sale;

                /** @var CounterUpdate $counterUpdate */
                $counterUpdate = $sale->counterUpdate;

                /** @var Counter $counter */
                $counter = $counterUpdate->counter;

                /** @var Cashier $cashier */
                $cashier = $counterUpdate->cashier;

                /** @var Employee $employee */
                $employee = $cashier->employee;

                $employeeName = $employee->getFullName();

                /** @var Location $location */
                $location = $counter->location;

                /** @var ?SalePriceOverride $salePriceOverride */
                $salePriceOverride = $saleDiscount->discountable;

                if ($salePriceOverride instanceof SalePriceOverride) {
                    $salePriceOverrideNegotiator = $salePriceOverride->negotiator;

                    /** @var Employee $salePriceOverrideEmployee */
                    $salePriceOverrideEmployee = $salePriceOverrideNegotiator->employee;

                    $employeeName = Str::of($salePriceOverride->negotiator_type)->replace(
                        '_',
                        ' '
                    )->title()->value() . ' : ' . $salePriceOverrideEmployee->getFullName();
                }

                $discountsAmount = $saleDiscount->amount;

                $amount = $sale->total_amount_paid + $sale->cart_discount_amount;
                $totalCartAmount += $amount;
                $totalCartDiscount += $discountsAmount;
                $totalNetSales += $amount - $discountsAmount;
                $totalVariation += $discountsAmount;

                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
                $happenedAt = $happenedAtFormat->format('d-m-Y');

                $locationSales['sales_data'][] = [
                    'location_code' => $location->code,
                    'counter_code' => $counter->name,
                    'cashier_code' => $employee->staff_id,
                    'employee_name' => $employeeName,
                    'offline_sale_id' => $sale->offline_sale_id,
                    'date' => $happenedAt,
                    'cart_total' => CommonFunctions::currencyFormat((float) $amount),
                    'cart_discount' => CommonFunctions::currencyFormat((float) $discountsAmount),
                    'percentage' => $amount ? CommonFunctions::currencyFormat($discountsAmount * 100 / $amount) : 0,
                    'net_sales' => CommonFunctions::currencyFormat((float) $amount - $discountsAmount),
                    'variation' => '-' . CommonFunctions::currencyFormat((float) $discountsAmount),
                ];
            }

            $locationSales['location_code'] = '';
            $locationSales['counter_code'] = '';
            $locationSales['cashier_code'] = '';
            $locationSales['employee_name'] = '';
            $locationSales['offline_sale_id'] = '';
            $locationSales['date'] = '';
            $locationSales['grand_cart_total'] = CommonFunctions::currencyFormat((float) $totalCartAmount);
            $locationSales['grand_cart_discount'] = CommonFunctions::truncateDecimal((float) $totalCartDiscount);
            $locationSales['percentage'] = '';
            $locationSales['grand_net_sales'] = CommonFunctions::truncateDecimal((float) $totalNetSales);
            $locationSales['grand_variation'] = CommonFunctions::truncateDecimal((float) $totalVariation);

            $locationsSales[] = $locationSales;
        }

        $columnsWithEmployeeName = [
            'Location Code',
            'Counter Code',
            'Cashier Code',
            'Employee Name',
            'Offline Sale Id',
            'Date',
            'Cart Total',
            'Cart Discount',
            'Percentage',
            'Net Sales',
            'Variation',
        ];

        return [$locationsSales, $columnsWithEmployeeName, $dateRange];
    }
}
