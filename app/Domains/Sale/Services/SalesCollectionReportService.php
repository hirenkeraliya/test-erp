<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SalesCollectionFilterTypes;
use App\Domains\Sale\Enums\SalesCollectionReportTypes;
use App\Domains\Sale\Exports\SalesCollectionByCashierExport;
use App\Domains\Sale\Exports\SalesCollectionByCounterAndByCashierExport;
use App\Domains\Sale\Exports\SalesCollectionByCounterExport;
use App\Domains\Sale\Exports\SalesCollectionByCurrentAndPreviousDay;
use App\Domains\Sale\Exports\SalesCollectionByDateAndBrandExport;
use App\Domains\Sale\Exports\SalesCollectionByDateExport;
use App\Domains\Sale\Exports\SalesCollectionByMonthAndBrandExport;
use App\Domains\Sale\Exports\SalesCollectionByOnlyTotalExport;
use App\Domains\Sale\Exports\SalesCollectionByReceiptExport;
use App\Domains\Sale\Exports\SalesCollectionBySummaryDetailsExport;
use App\Domains\Sale\Exports\SalesCollectionByTimeExport;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesCollectionReportService
{
    public function print(int $companyId, array $filterData): string
    {
        $html = '';

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_DATE->value) {
            $html = $this->renderPreparedSalesByDate($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_DATE_AND_BRAND->value) {
            $html = $this->renderPreparedSalesByDateAndBrand($filterData, $company);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_RECEIPT->value) {
            $html = $this->renderPreparedSalesByReceipt($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_CASHIER->value) {
            $html = $this->renderPreparedSalesByCashier($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_COUNTER->value) {
            $html = $this->renderPreparedSalesByCounter($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_TIME->value) {
            $html = $this->renderPreparedSalesByTime($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_COUNTER_AND_CASHIER->value) {
            $html = $this->renderPreparedSalesByCounterAndByCashier($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_SUMMARY->value) {
            $html = $this->renderPreparedSalesOnlyTotals($filterData, $company, $locations);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_CURRENT_DAY_VS_PREVIOUS_DAY->value) {
            $html = $this->renderPreparedSalesForCurrentAndPreviousDay($company, $filterData);
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_SUMMARY_DETAILS->value) {
            $salesBySummaryDetailsService = resolve(SalesBySummaryDetailsService::class);
            $html = $salesBySummaryDetailsService->renderPreparedSalesBySummaryDetails(
                $filterData,
                $company,
                $locations
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_SUMMARY_MONTH_AND_BRAND->value) {
            return $this->renderPreparedSalesBySummaryMonthAndBrand($filterData, $company);
        }

        return $html;
    }

    public function renderPreparedSalesByDate(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$locationPayments, $columns, $dateRange] = $customReportService->preparedPaymentsByDate(
            $filterData,
            $locations
        );

        return view('prints.sales_collection', [
            'locationPayments' => $locationPayments,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesByDateAndBrand(array $filterData, Company $company): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$brandLocationsSalesCollection, $grandTotal, $columns, $dateRange] = $customReportService->preparedSalesCollectionByDateAndBrand(
            $filterData,
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.sales_collection_by_date_and_brand', [
            'brandLocationsSalesCollection' => $brandLocationsSalesCollection,
            'grandTotal' => $grandTotal,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'currencySymbol' => $currency->getSymbol(),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesBySummaryMonthAndBrand(array $filterData, Company $company): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$brandLocationsSalesCollection, $grandTotal, $columns, $dateRange] = $customReportService->preparedSalesCollectionBySummaryMonthAndBrand(
            $filterData,
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.sales_collection_by_summary_month_and_brand', [
            'brandLocationsSalesCollection' => $brandLocationsSalesCollection,
            'grandTotal' => $grandTotal,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'currencySymbol' => $currency->getSymbol(),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesOnlyTotals(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$locationPayments, $columns, $dateRange] = $customReportService->preparedPaymentsByOnlyTotals(
            $filterData,
            $locations
        );

        return view('prints.sales_collection_only_totals', [
            'locationPayments' => $locationPayments,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesByReceipt(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$locationsSales, $columns, $dateRange] = $customReportService->preparedSalesByReceipt($filterData, $locations);

        return view('prints.sales_collection_by_receipt', [
            'locationsSales' => $locationsSales,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesByCashier(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$locationPayments, $columns, $dateRange] = $customReportService->preparedSalesByCashier(
            $filterData,
            $locations
        );

        return view('prints.sales_collection_by_cashier', [
            'locationPayments' => $locationPayments,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesByCounter(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$locationPayments, $columns, $dateRange] = $customReportService->preparedSalesByCounter(
            $filterData,
            $locations
        );

        return view('prints.sales_collection_by_counter', [
            'locationPayments' => $locationPayments,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesByTime(array $filterData, Company $company, Collection $locations): string
    {
        $customReportService = resolve(CustomReportService::class);
        [$locationsSales, $columns, $dateRange] = $customReportService->preparedSalesByTime($filterData, $locations);

        return view('prints.sales_collection_by_time', [
            'locationsSales' => $locationsSales,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedSalesByCounterAndByCashier(
        array $filterData,
        Company $company,
        Collection $locations
    ): string {
        [$locationPayments, $columns, $dateRange] = $this->preparedSalesByCounterAndByCashier($filterData, $locations);

        return view('prints.sales_collection_by_counter_and_cashier', [
            'locationPayments' => $locationPayments,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function exportSaleCollection(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        $saleCollections = [];
        $columns = [];
        $filterBy = $this->filterBy($filterData);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);
        $customReportService = resolve(CustomReportService::class);

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_DATE->value) {
            [$saleCollections, $columns, $dateRange] = $customReportService->preparedPaymentsByDate(
                $filterData,
                $locations
            );

            return Excel::download(
                new SalesCollectionByDateExport(
                    $saleCollections,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_DATE_AND_BRAND->value) {
            [$brandLocationsSalesCollection, $grandTotal, $columns, $dateRange] = $customReportService->preparedSalesCollectionByDateAndBrand(
                $filterData,
            );

            $currencyQueries = resolve(CurrencyQueries::class);
            $currency = $currencyQueries->getByCompanyId($companyId);

            return Excel::download(
                new SalesCollectionByDateAndBrandExport(
                    $brandLocationsSalesCollection,
                    $grandTotal,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $currency->getSymbol(),
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_CASHIER->value) {
            [$locationPayments, $columns, $dateRange] = $customReportService->preparedSalesByCashier(
                $filterData,
                $locations
            );

            return Excel::download(
                new SalesCollectionByCashierExport(
                    $locationPayments,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_COUNTER->value) {
            [$locationPayments, $columns, $dateRange] = $customReportService->preparedSalesByCounter(
                $filterData,
                $locations
            );

            return Excel::download(
                new SalesCollectionByCounterExport(
                    $locationPayments,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_COUNTER_AND_CASHIER->value) {
            [$locationPayments, $columns, $dateRange] = $this->preparedSalesByCounterAndByCashier(
                $filterData,
                $locations
            );

            return Excel::download(
                new SalesCollectionByCounterAndByCashierExport(
                    $locationPayments,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_TIME->value) {
            [$saleCollections, $columns, $dateRange] = $customReportService->preparedSalesByTime(
                $filterData,
                $locations
            );

            return Excel::download(
                new SalesCollectionByTimeExport(
                    $saleCollections,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_SUMMARY->value) {
            [$saleCollections, $columns, $dateRange] = $customReportService->preparedPaymentsByOnlyTotals(
                $filterData,
                $locations
            );

            return Excel::download(
                new SalesCollectionByOnlyTotalExport(
                    $saleCollections,
                    $columns,
                    $company,
                    $dateRange,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_CURRENT_DAY_VS_PREVIOUS_DAY->value) {
            [$preparedSales, $grandTotal, $mainColumns, $columns, $yearComparisons, $previousDates] = $this->preparedSalesDataBasedOnCurrentAndPrevious(
                $filterData
            );

            return Excel::download(
                new SalesCollectionByCurrentAndPreviousDay(
                    $company,
                    $preparedSales,
                    $grandTotal,
                    $mainColumns,
                    $columns,
                    $filterBy,
                    $filterData['date'],
                    $yearComparisons,
                    $previousDates,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_SUMMARY_DETAILS->value) {
            $salesBySummaryDetailsService = resolve(SalesBySummaryDetailsService::class);
            [$locationsSales, $totalQuantity, $totalGrossSales, $totalDiscountAmount, $totalNetSaleExclusiveTax, $totalNetSaleInclusiveTax, $totalTaxAmount] = $salesBySummaryDetailsService->preparedGeneralSalesBySummary(
                $filterData,
                $locations
            );

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(
                new SalesCollectionBySummaryDetailsExport(
                    $locationsSales,
                    $totalQuantity,
                    $totalGrossSales,
                    $totalDiscountAmount,
                    $totalNetSaleExclusiveTax,
                    $totalNetSaleInclusiveTax,
                    $totalTaxAmount,
                    $company,
                    $dateRange,
                    $filterBy,
                    $filterData['e_invoice_submitted']
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === SalesCollectionReportTypes::BY_SUMMARY_MONTH_AND_BRAND->value) {
            [$brandLocationsSalesCollection, $grandTotal, $columns, $dateRange] = $customReportService->preparedSalesCollectionBySummaryMonthAndBrand(
                $filterData,
            );

            $currencyQueries = resolve(CurrencyQueries::class);
            $currency = $currencyQueries->getByCompanyId($companyId);

            return Excel::download(
                new SalesCollectionByMonthAndBrandExport(
                    $brandLocationsSalesCollection,
                    $grandTotal,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $currency->getSymbol(),
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        [$saleCollections, $columns, $dateRange] = $customReportService->preparedSalesByReceipt(
            $filterData,
            $locations
        );

        return Excel::download(
            new SalesCollectionByReceiptExport(
                $saleCollections,
                $columns,
                $company,
                $dateRange,
                $filterBy,
                $filterData['e_invoice_submitted']
            ),
            $filename
        );
    }

    /**
     * @return array<int, mixed[]>
     */
    private function preparedSalesByCounterAndByCashier(array $filterData, Collection $locations): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $salePayments = $counterUpdateQueries->getForSalesCollectionByFilterCashier($filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationPayments = [];

        $columns = [
            'counter' => 'Counter',
            'cashier' => 'Cashier',
            'orders' => 'Orders',
            'sales_collection' => 'Sales Collection',
        ];

        foreach ($locations as $location) {
            $totals = [
                'counter' => 'Grand Total',
                'cashier' => '',
                'orders' => 0,
                'sales_collection' => 0,
                'sales_round_off' => 0,
                'total_tax_amount' => 0,
            ];
            $locationSaleDetails = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'payment_details' => [],
            ];

            $groupByLocationSalePayments = $salePayments->where('counter.location_id', $location->id);

            foreach ($groupByLocationSalePayments->groupBy(['counter_id', 'cashier_id']) as $counterSalePayments) {
                $datePaymentRecords = [];
                $datePaymentRecords['counter'] = '';
                $datePaymentRecords['cashier'] = '';
                $datePaymentRecords['orders'] = 0;
                $datePaymentRecords['sales_collection'] = 0;

                foreach ($counterSalePayments as $counterSalePayment) {
                    foreach ($counterSalePayment as $cashierSalePayment) {
                        $totalSales = ($cashierSalePayment->total_sales + $cashierSalePayment->total_sale_returns);

                        $datePaymentRecords['counter'] = $cashierSalePayment->counter->name;
                        $datePaymentRecords['cashier'] = $cashierSalePayment->cashier->employee->getFullName();
                        $datePaymentRecords['orders'] += $totalSales;

                        $salesCollectionAmount = $cashierSalePayment->sales_collection_amount;
                        $salesCollectionAmount += RoundOffConfiguration::roundOffCalculationFor(
                            (string) $salesCollectionAmount
                        );

                        $datePaymentRecords['sales_collection'] += $salesCollectionAmount;

                        $totals['orders'] += $totalSales;
                        $totals['sales_collection'] += $salesCollectionAmount;
                        $totals['sales_round_off'] += $cashierSalePayment->total_sales_round_off;
                        $totals['total_tax_amount'] += $cashierSalePayment->total_tax_amount;

                        foreach ($cashierSalePayment->payments as $payment) {
                            $paymentType = $payment->paymentType->name;
                            $paymentName = strtolower(str_replace(' ', '_', $paymentType));
                            $columns[$paymentName] = $paymentType;

                            if (! array_key_exists($paymentName, $datePaymentRecords)) {
                                $datePaymentRecords[$paymentName] = 0;
                            }

                            $totalAmount = $payment->total_amount;
                            $totalAmount += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmount);

                            $datePaymentRecords[$paymentName] += $totalAmount;

                            if (! array_key_exists($paymentName, $totals)) {
                                $totals[$paymentName] = 0;
                            }

                            $totals[$paymentName] += $totalAmount;
                        }
                    }
                }

                $locationSaleDetails['payment_details'][] = $datePaymentRecords;
            }

            $locationSaleDetails['totals'] = $totals;
            $locationPayments[] = $locationSaleDetails;
        }

        return [$locationPayments, $columns, $dateRange];
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

    public function renderPreparedSalesForCurrentAndPreviousDay(Company $company, array $filterData): string
    {
        [$preparedSales, $grandTotal, $mainColumns, $columns, $yearComparisons, $previousDates] = $this->preparedSalesDataBasedOnCurrentAndPrevious(
            $filterData
        );

        return view('prints.sales_collection_by_current_and_previous_day', [
            'locationSales' => $preparedSales,
            'selectedDate' => $filterData['date'],
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'mainColumns' => $mainColumns,
            'yearComparisons' => $yearComparisons,
            'previousDates' => $previousDates,
            'grandTotals' => $grandTotal,
            'filterBy' => $this->filterBy($filterData),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    private function preparedSalesDataBasedOnCurrentAndPrevious(array $filterData): array
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $salesGroupedByRegionAndDate = $counterUpdateQueries->getSalesAndReturnDataByDate($filterData)->groupBy(
            ['region_name', 'date']
        );

        $records = [];
        $columns = [
            'location_name' => '',
        ];

        /** @var Carbon $filterDateCarbon */
        $filterDateCarbon = Carbon::createFromFormat('Y-m-d', $filterData['date']);

        $mainColumns = ['Location', $filterDateCarbon->format('d-M'), 'Sales As At Yesterday', '% As At Yesterday'];

        /** @var array $grandTotal */
        $grandTotal = [
            'location_name' => 'Grand Total',
            'year_comparison' => [],
        ];

        $previousDates = [];
        $yearComparison = [];

        foreach ($salesGroupedByRegionAndDate as $regionName => $salesGroupedByDate) {
            /** @var array $total */
            $total = [
                'region_name' => $regionName,
            ];

            foreach ($salesGroupedByDate as $date => $salesGrouped) {
                foreach ($salesGrouped as $sale) {
                    if (! array_key_exists($sale->region_name, $records)) {
                        $records[$sale->region_name] = [];
                    }

                    if (! array_key_exists($sale->location_name, $records[$sale->region_name])) {
                        $records[$sale->region_name][$sale->location_name] = [];
                    }

                    if (! array_key_exists(
                        $date,
                        $records[$sale->region_name][$sale->location_name]
                    ) && $filterDateCarbon->format('d-m-Y') === $date) {
                        $records[$sale->region_name][$sale->location_name][$date] = 0;
                    }

                    if (! array_key_exists(
                        $filterDateCarbon->format('d-m-Y'),
                        $records[$sale->region_name][$sale->location_name]
                    )) {
                        $records[$sale->region_name][$sale->location_name][$filterDateCarbon->format('d-m-Y')] = 0;
                    }

                    if (! array_key_exists($filterDateCarbon->format('d-m-Y'), $total)) {
                        $total[$filterDateCarbon->format('d-m-Y')] = 0;
                    }

                    if (! array_key_exists($filterDateCarbon->format('d-m-Y'), $grandTotal)) {
                        $grandTotal[$filterDateCarbon->format('d-m-Y')] = 0;
                    }

                    if (! array_key_exists('previous_date', $total)) {
                        $total['previous_date'] = [];
                    }

                    if (! array_key_exists($date, $total['previous_date']) && $filterDateCarbon->format(
                        'd-m-Y'
                    ) !== $date) {
                        $total['previous_date'][$date] = 0;
                    }

                    if (! array_key_exists($date, $total) && $filterDateCarbon->format('d-m-Y') === $date) {
                        $total[$date] = 0;
                    }

                    if (! array_key_exists($date, $grandTotal) && $filterDateCarbon->format('d-m-Y') === $date) {
                        $grandTotal[$date] = 0;
                    }

                    if (! array_key_exists('previous_date', $grandTotal)) {
                        $grandTotal['previous_date'] = [];
                    }

                    if (! array_key_exists(
                        $date,
                        $grandTotal['previous_date']
                    ) && $filterDateCarbon->format('d-m-Y') !== $date) {
                        $grandTotal['previous_date'][$date] = 0;
                    }

                    if (! array_key_exists($date, $columns) && $filterDateCarbon->format('d-m-Y') === $date) {
                        $columns[$date] = $date;
                    }

                    if (! array_key_exists('previous_date', $records[$sale->region_name][$sale->location_name])) {
                        $records[$sale->region_name][$sale->location_name]['previous_date'] = [];
                    }

                    if (! array_key_exists(
                        $date,
                        $records[$sale->region_name][$sale->location_name]['previous_date']
                    ) && $filterDateCarbon->format('d-m-Y') !== $date) {
                        $records[$sale->region_name][$sale->location_name]['previous_date'][$date] = 0;
                    }

                    if ($filterDateCarbon->format('d-m-Y') !== $date) {
                        $records[$sale->region_name][$sale->location_name]['previous_date'][$date] += $sale->total;
                        $total['previous_date'][$date] += $sale->total;
                        $grandTotal['previous_date'][$date] += $sale->total;
                        if (! in_array($date, $previousDates)) {
                            $previousDates[] = $date;
                        }
                    } else {
                        $records[$sale->region_name][$sale->location_name][$date] += $sale->total;
                        $total[$date] += $sale->total;
                        $grandTotal[$date] += $sale->total;
                    }

                    $records[$sale->region_name][$sale->location_name]['location_name'] = $sale->location_name;
                }
            }

            $records[$regionName]['total'] = $total;
        }

        $key = 0;
        $preparedSales = [];
        /** @var Carbon $yesterdayDateCarbon */
        $yesterdayDateCarbon = Carbon::createFromFormat('Y-m-d', $filterData['date']);
        $yesterdayDate = $yesterdayDateCarbon->subDays();
        $key = 0;
        $totalRecordsCounts = 1;

        foreach ($records as $regionWiseRecords) {
            foreach ($regionWiseRecords as $storeWiseRecords) {
                foreach ($previousDates as $previousDate) {
                    if (! array_key_exists($previousDate, $storeWiseRecords['previous_date'])) {
                        $storeWiseRecords['previous_date'][$previousDate] = 0;
                    }
                }

                $preparedSales[$key] = $storeWiseRecords;

                $thisYearSales = 0;
                if (array_key_exists($yesterdayDate->format('d-m-Y'), $storeWiseRecords['previous_date'])) {
                    $thisYearSales = (float) $storeWiseRecords['previous_date'][$yesterdayDate->format('d-m-Y')];
                }

                $totalRecordsCounts = count($storeWiseRecords['previous_date']) > 1 ? count(
                    $storeWiseRecords['previous_date']
                ) - 1 : count($storeWiseRecords['previous_date']);

                $lastYearTodayDate = $yesterdayDate->copy();
                for ($i = 0; $i < $totalRecordsCounts; $i++) {
                    $lastYearTodayDate = $lastYearTodayDate->subYear();
                    $lastYearSaleAvg = 0;
                    if (array_key_exists($lastYearTodayDate->format('d-m-Y'), $storeWiseRecords['previous_date'])) {
                        $lastYearSales = (float) $storeWiseRecords['previous_date'][$lastYearTodayDate->format(
                            'd-m-Y'
                        )];
                        if ($lastYearSales > 0) {
                            $lastYearSaleAvg = CommonFunctions::numberFormat(
                                (($thisYearSales - $lastYearSales) / $lastYearSales) * 100
                            );
                        }
                    }

                    $columnKey = $yesterdayDate->format('Y') . '-' . $lastYearTodayDate->format('Y');
                    $preparedSales[$key]['year_comparison'][$columnKey] = $lastYearSaleAvg;
                    if (! in_array($columnKey, $yearComparison)) {
                        $yearComparison[] = $columnKey;
                    }

                    if (! in_array($columnKey, $grandTotal['year_comparison'])) {
                        $grandTotal['year_comparison'][$columnKey] = 0;
                    }
                }

                $key += 1;
            }
        }

        $lastYearTodayDate = $yesterdayDate->copy();
        $lastYearTodayDate = $lastYearTodayDate->subYear();

        $lastYearSaleAvgForGrandTotal = 0;

        $thisYearSalesGrandTotal = 0;
        if (array_key_exists($yesterdayDate->format('d-m-Y'), $grandTotal['previous_date'])) {
            $thisYearSalesGrandTotal = (float) $grandTotal['previous_date'][$yesterdayDate->format('d-m-Y')];
        }

        if (array_key_exists($lastYearTodayDate->format('d-m-Y'), $grandTotal['previous_date'])) {
            $lastYearSalesForGrandTotal = (float) $grandTotal['previous_date'][$lastYearTodayDate->format('d-m-Y')];
            if ($lastYearSalesForGrandTotal > 0) {
                $lastYearSaleAvgForGrandTotal = CommonFunctions::numberFormat(
                    (($thisYearSalesGrandTotal - $lastYearSalesForGrandTotal) / $lastYearSalesForGrandTotal) * 100
                );
            }
        }

        $columnKey = $yesterdayDate->format('Y') . '-' . $lastYearTodayDate->format('Y');
        $grandTotal['year_comparison'][$columnKey] = $lastYearSaleAvgForGrandTotal;

        $columns = collect($columns)->sortKeysDesc()->toArray();
        $preparedSalesKeys = $preparedSales;
        $preparedSalesKeys = current($preparedSalesKeys);

        /* @phpstan-ignore-next-line */
        collect($preparedSalesKeys)->keys()->each(function ($key) use (&$columns): void {
            if (! array_key_exists($key, $columns)) {
                $columns[$key] = $key;
            }
        });

        return [
            $preparedSales,
            $grandTotal,
            $mainColumns,
            $columns,
            collect($yearComparison)->sortKeysDesc()->toArray(),
            collect($previousDates)->sortKeysDesc()->toArray(),
        ];
    }
}
