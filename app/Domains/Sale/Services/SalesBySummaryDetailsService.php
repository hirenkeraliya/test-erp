<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Sale\Enums\SalesCollectionFilterTypes;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesBySummaryDetailsService
{
    public function renderPreparedSalesBySummaryDetails(
        array $filterData,
        Company $company,
        Collection $locations
    ): string {
        [$locationSales, $totalQuantity, $totalGrossSales, $totalDiscountAmount, $totalNetSaleExclusiveTax, $totalNetSaleInclusiveTax, $totalTaxAmount] = $this->preparedGeneralSalesBySummary(
            $filterData,
            $locations
        );

        $customReportService = resolve(CustomReportService::class);

        return view('prints.sales_by_summary_details', [
            'locationSales' => $locationSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'totalQty' => $totalQuantity,
            'totalGross' => $totalGrossSales,
            'totalDiscount' => $totalDiscountAmount,
            'totalNetSaleEx' => $totalNetSaleExclusiveTax,
            'totalTaxAmount' => $totalTaxAmount,
            'totalNetSaleIn' => $totalNetSaleInclusiveTax,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function preparedGeneralSalesBySummary(array $filterData, Collection $locations): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleSummaryDetails = $counterUpdateQueries->getForSalesCollectionBySummaryDetails($filterData);
        $totalQuantity = 0;
        $totalGrossSales = 0;
        $totalDiscountAmount = 0;
        $totalNetSaleExclusiveTax = 0;
        $totalNetSaleInclusiveTax = 0;
        $totalTaxAmount = 0;

        $locationsSales = [];

        foreach ($locations as $location) {
            $locationSales = [
                'location_code' => $location->code,
                'location_name' => $location->name,
                'quantity' => 0,
                'gross_sales_exclusive_tax' => 0,
                'discount_amount' => 0,
                'net_sales_exclusive_tax' => 0,
                'tax_amount' => 0,
                'net_sales_inclusive_tax' => 0,
            ];

            $locationSaleSummaryDetails = $saleSummaryDetails->where('counter.location_id', $location->id);

            $locationTotalQuantity = 0;
            $locationTotalGrossSales = 0;
            $locationTotalDiscountAmount = 0;
            $locationTotalNetSaleExclusiveTax = 0;
            $locationTotalNetSaleInclusiveTax = 0;
            $locationTotalTaxAmount = 0;

            foreach ($locationSaleSummaryDetails as $locationSaleSummaryDetail) {
                $discountAmount = ($locationSaleSummaryDetail->total_item_wise_discount_amount + $locationSaleSummaryDetail->total_cart_wide_discount_amount);
                $discountAmount += RoundOffConfiguration::roundOffCalculationFor((string) $discountAmount);

                $grossSales = ($locationSaleSummaryDetail->sales_collection_amount + $discountAmount);
                $netSaleExclusiveTax = ($locationSaleSummaryDetail->sales_collection_amount - $locationSaleSummaryDetail->total_tax_amount);
                $netSaleInclusiveTax = $locationSaleSummaryDetail->sales_collection_amount;
                $quantity = $locationSaleSummaryDetail->sales->sum(
                    'quantity'
                ) - $locationSaleSummaryDetail->saleReturns->sum('quantity');

                $totalQuantity += $quantity;
                $totalGrossSales += $grossSales;
                $totalDiscountAmount += $discountAmount;
                $totalNetSaleExclusiveTax += $netSaleExclusiveTax;
                $totalNetSaleInclusiveTax += $netSaleInclusiveTax;
                $totalTaxAmount += $locationSaleSummaryDetail->total_tax_amount;

                $locationTotalQuantity += $quantity;
                $locationTotalGrossSales += $grossSales;
                $locationTotalDiscountAmount += $discountAmount;
                $locationTotalNetSaleExclusiveTax += $netSaleExclusiveTax;
                $locationTotalNetSaleInclusiveTax += $netSaleInclusiveTax;
                $locationTotalTaxAmount += $locationSaleSummaryDetail->total_tax_amount;
            }

            $locationSales['quantity'] = CommonFunctions::truncateDecimal((float) $locationTotalQuantity);
            $locationSales['gross_sales_exclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $locationTotalGrossSales
            );
            $locationSales['discount_amount'] = CommonFunctions::currencyFormat((float) $locationTotalDiscountAmount);
            $locationSales['net_sales_exclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $locationTotalNetSaleExclusiveTax
            );
            $locationSales['tax_amount'] = CommonFunctions::currencyFormat((float) $locationTotalTaxAmount);
            $locationSales['net_sales_inclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $locationTotalNetSaleInclusiveTax
            );
            $locationsSales[] = $locationSales;
        }

        return [
            $locationsSales,
            $totalQuantity,
            $totalGrossSales,
            $totalDiscountAmount,
            $totalNetSaleExclusiveTax,
            $totalNetSaleInclusiveTax,
            $totalTaxAmount,
        ];
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $counterQueries = resolve(CounterQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === SalesCollectionFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                SalesCollectionFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === SalesCollectionFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                SalesCollectionFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return SalesCollectionFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
