<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\Enums\GeneralSalesFilterTypes;
use App\Domains\Sale\Enums\GeneralSalesReportTypes;
use App\Domains\Sale\Exports\GeneralSalesReportByAttributeExport;
use App\Domains\Sale\Exports\GeneralSalesReportByColorAndSizeExport;
use App\Domains\Sale\Exports\GeneralSalesReportByCurrentVsPreviousExport;
use App\Domains\Sale\Exports\GeneralSalesReportByDateAndBrandExport;
use App\Domains\Sale\Exports\GeneralSalesReportByItemAndReceiptExport;
use App\Domains\Sale\Exports\GeneralSalesReportByProductExport;
use App\Domains\Sale\Exports\GeneralSalesReportByPromoterExport;
use App\Domains\Sale\Exports\GeneralSalesReportByReceiptAndItemExport;
use App\Domains\Sale\Exports\GeneralSalesReportBySummaryExport;
use App\Domains\Sale\Exports\GeneralSalesReportBySummaryMonthExport;
use App\Domains\SaleItem\SaleItemQueries;
use App\Models\Brand;
use App\Models\Company;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneralSalesReportService
{
    public function print(int $companyId, array $filterData, bool $excludeProductsWithNoPrice): string
    {
        $html = '';

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_PRODUCT->value) {
            $html = $this->renderPreparedGeneralSalesByProduct($filterData, $company, $excludeProductsWithNoPrice);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_ITEM_AND_RECEIPT->value) {
            $html = $this->renderPreparedGeneralSalesByItemAndReceipt(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_RECEIPT_AND_ITEM->value) {
            $html = $this->renderPreparedGeneralSalesByReceiptAndItem(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_COLOR_AND_SIZE->value) {
            return $this->renderPreparedGeneralSalesByColorAndSize($filterData, $company, $excludeProductsWithNoPrice);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_ATTRIBUTE->value) {
            return $this->renderPreparedGeneralSalesByAttribute($filterData, $company, $excludeProductsWithNoPrice);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_SUMMARY->value) {
            return $this->renderPreparedGeneralSalesBySummary($filterData, $company, $excludeProductsWithNoPrice);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_DATE_AND_BRAND->value) {
            return $this->renderPreparedGeneralSalesByDateAndBrand($filterData, $company, $excludeProductsWithNoPrice);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_PROMOTER_SUMMARY->value) {
            return $this->renderPreparedGeneralSalesByPromoterSummary(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_CURRENT_DAY_VS_PREVIOUS_DAY->value) {
            return $this->renderPreparedGeneralSalesByCurrentDayVsPreviousDay(
                $company,
                $filterData,
                $excludeProductsWithNoPrice
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_SUMMARY_MONTH->value) {
            return $this->renderPreparedGeneralSalesBySummaryMonth(
                $company,
                $filterData,
                $excludeProductsWithNoPrice
            );
        }

        return $html;
    }

    public function renderPreparedGeneralSalesByProduct(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        [$locationSales, $columns] = $this->preparedGeneralSalesByProduct(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );

        $customReportService = resolve(CustomReportService::class);

        $filterBy = null;

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        return view('prints.general_sales_by_product', [
            'locationSales' => $locationSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $filterBy ?: $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesBySummary(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        [$locationSales, $columns] = $this->preparedGeneralSalesBySummary(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );

        $customReportService = resolve(CustomReportService::class);

        return view('prints.general_sales_by_summary', [
            'locationSales' => $locationSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesByDateAndBrand(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        [$brandLocationsSales, $grandTotal, $columns] = $this->preparedGeneralSalesByDateAndBrand(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );
        $customReportService = resolve(CustomReportService::class);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($company->id);

        return view('prints.general_sales_by_date_and_brand', [
            'brandLocationsSales' => $brandLocationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'grandTotal' => $grandTotal,
            'currencySymbol' => $currency->getSymbol(),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesByPromoterSummary(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        [$locationSales, $columns] = $this->preparedGeneralSalesByPromoter(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );

        $customReportService = resolve(CustomReportService::class);

        $filterBy = null;

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        return view('prints.general_sales_by_promoter', [
            'locationSales' => $locationSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $filterBy ?: $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesByItemAndReceipt(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        [$locationSales, $columns] = $this->preparedGeneralSalesByItemAndReceipt(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );

        $customReportService = resolve(CustomReportService::class);

        $filterBy = null;

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        return view('prints.general_sales_by_item_and_receipt', [
            'company' => $company,
            'locationSales' => $locationSales,
            'columns' => $columns,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $filterBy ?: $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function exportGeneralSalesReport(
        int $companyId,
        array $filterData,
        string $filename,
        bool $excludeProductsWithNoPrice
    ): BinaryFileResponse {
        $locationSales = [];
        $columns = [];

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $filterBy = $this->filterBy($filterData);

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        $reportType = GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']);

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_ITEM_AND_RECEIPT->value) {
            [$locationSales, $columns, $dateRange] = $this->preparedGeneralSalesByItemAndReceiptForExport(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            return Excel::download(
                new GeneralSalesReportByItemAndReceiptExport(
                    $locationSales,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $reportType,
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_RECEIPT_AND_ITEM->value) {
            [$locationSales, $columns, $dateRange] = $this->preparedGeneralSalesByReceiptAndItemForExport(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            return Excel::download(
                new GeneralSalesReportByReceiptAndItemExport(
                    $locationSales,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $reportType,
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_SUMMARY->value) {
            [$locationSales, $columns] = $this->preparedGeneralSalesBySummary(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(
                new GeneralSalesReportBySummaryExport(
                    $locationSales,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $reportType,
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_SUMMARY_MONTH->value) {
            [$locationSales, $columns] = $this->preparedGeneralSalesBySummaryMonth(
                $filterData,
                $excludeProductsWithNoPrice
            );

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(
                new GeneralSalesReportBySummaryMonthExport(
                    $locationSales,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $reportType,
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_DATE_AND_BRAND->value) {
            [$brandLocationsSales, $grandTotal, $columns] = $this->preparedGeneralSalesByDateAndBrand(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            $currencyQueries = resolve(CurrencyQueries::class);
            $currency = $currencyQueries->getByCompanyId($company->id);

            return Excel::download(
                new GeneralSalesReportByDateAndBrandExport(
                    $brandLocationsSales,
                    $grandTotal,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $reportType,
                    $currency->getSymbol(),
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_PROMOTER_SUMMARY->value) {
            [$locationSales, $columns] = $this->preparedGeneralSalesByPromoter(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(
                new GeneralSalesReportByPromoterExport(
                    $locationSales,
                    $columns,
                    $dateRange,
                    $company,
                    $filterBy,
                    $reportType,
                    $filterData['e_invoice_submitted'],
                ),
                $filename
            );
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_COLOR_AND_SIZE->value) {
            $locationSales = $this->preparedGeneralSalesByColorAndSize(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            $columns = [
                'Counter Name',
                'Product No',
                'Promoter',
                'Description',
                'Qty',
                'Gross Sales',
                'Discount',
                'Net Sales',
                'UPC',
                'Color',
                'Size',
                'Quantity',
            ];

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(new GeneralSalesReportByColorAndSizeExport($locationSales, $columns), $filename);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_CURRENT_DAY_VS_PREVIOUS_DAY->value) {
            [$preparedSales, $grandTotals, $mainColumns, $columns, $yearComparisons, $previousDates] = $this->preparedGeneralSalesByCurrentDayVsPreviousDay(
                $company,
                $filterData,
                $excludeProductsWithNoPrice
            );

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(new GeneralSalesReportByCurrentVsPreviousExport(
                $company,
                $preparedSales,
                $grandTotals,
                $columns,
                $mainColumns,
                $yearComparisons,
                $previousDates,
                $filterData['date'],
                GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
                $this->filterBy($filterData),
                $filterData['e_invoice_submitted'],
            ), $filename);
        }

        if ((int) $filterData['report_type'] === GeneralSalesReportTypes::BY_ATTRIBUTE->value) {
            $locationSales = $this->preparedGeneralSalesByAttribute(
                $filterData,
                $company,
                $excludeProductsWithNoPrice
            );

            $columns = [
                'Counter Name',
                'Product No',
                'Promoter',
                'Description',
                'Qty',
                'Gross Sales',
                'Discount',
                'Net Sales',
                'UPC',
                'Attribute',
                'Quantity',
            ];

            $customReportService = resolve(CustomReportService::class);
            $dateRange = $customReportService->prepareDateRange($filterData);

            return Excel::download(new GeneralSalesReportByAttributeExport($locationSales, $columns), $filename);
        }

        [$locationSales, $columns] = $this->preparedGeneralSalesByProduct(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        return Excel::download(
            new GeneralSalesReportByProductExport(
                $locationSales,
                $columns,
                $dateRange,
                $company,
                $filterBy,
                $reportType,
                $filterData['e_invoice_submitted'],
            ),
            $filename
        );
    }

    public function renderPreparedGeneralSalesByReceiptAndItem(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        [$locationSales, $columns] = $this->preparedGeneralSalesByReceiptAndItem(
            $filterData,
            $company,
            $excludeProductsWithNoPrice
        );

        $customReportService = resolve(CustomReportService::class);
        $filterBy = null;

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        return view('prints.general_sales_by_receipt_and_item', [
            'company' => $company,
            'locationSales' => $locationSales,
            'columns' => $columns,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $filterBy ?: $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesByColorAndSize(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        $locationSales = $this->preparedGeneralSalesByColorAndSize($filterData, $company, $excludeProductsWithNoPrice);

        $customReportService = resolve(CustomReportService::class);
        $filterBy = null;

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        return view('prints.general_sales_by_color_and_size', [
            'company' => $company,
            'locationSales' => $locationSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $filterBy ?: $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesByAttribute(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): string {
        $locationSales = $this->preparedGeneralSalesByAttribute($filterData, $company, $excludeProductsWithNoPrice);

        $customReportService = resolve(CustomReportService::class);
        $filterBy = null;

        if ((int) $filterData['filter_by'] === GeneralSalesFilterTypes::BY_PROMOTER->value) {
            $filterBy = GeneralSalesFilterTypes::getFormattedCaseName(GeneralSalesFilterTypes::BY_PROMOTER->value);
        }

        return view('prints.general_sales_by_attribute', [
            'company' => $company,
            'locationSales' => $locationSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filterBy' => $filterBy ?: $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    /**
     * @return array<int, mixed>
     */
    private function preparedGeneralSalesByReceiptAndItem(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getForGeneralSalesReportBySalesDate($filterData, $excludeProductsWithNoPrice);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationSalesData = [];

        foreach ($locations as $location) {
            $total = [
                'counter_name' => 'Total',
                'product_no' => '',
                'receipt_data' => '',
                'qty' => 0,
                'gross_sales' => 0,
                'discount' => 0,
                'net_sales_exclusive_tax' => 0,
                'tax_amount' => 0,
                'net_sales_inclusive_tax' => 0,
            ];
            $locationSales = [];

            $groupSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id)->sortBy(
                'sale.happened_at'
            )->groupBy('sale_id');

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($groupSaleItems as $groupSaleItem) {
                $saleItem = $groupSaleItem->first();

                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleItem->sale->happened_at);
                $happenedAt = $happenedAtFormat->format('d M Y h:i:s A');

                $locationSales[$saleItem->sale_id]['sale'] = [
                    'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                    'product_no' => $saleItem->sale->offline_sale_id,
                    'receipt_data' => $happenedAt,
                    'qty' => '',
                    'gross_sales' => '',
                    'discount' => '',
                    'net_sales_exclusive_tax' => '',
                    'tax_amount' => '',
                    'net_sales_inclusive_tax' => '',
                ];

                foreach ($groupSaleItem->sortBy('sale.happened_at') as $saleItem) {
                    $promoters = $saleItem->promoters;
                    $allPromoters = [];

                    foreach ($promoters as $promoter) {
                        $employee = $promoter->employee;
                        $allPromoters[] = $employee->getFullName();
                    }

                    $allPromoters = implode(',', $allPromoters);
                    $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);

                    $total['qty'] += $saleItem->quantity;
                    $total['gross_sales'] += $grossSales;
                    $total['discount'] += $saleItem->total_discount_amount;
                    $total['net_sales_exclusive_tax'] += $saleItem->total_price_paid - $saleItem->total_tax_amount;
                    $total['tax_amount'] += $saleItem->total_tax_amount;
                    $total['net_sales_inclusive_tax'] += $saleItem->total_price_paid;

                    $locationSales[$saleItem->sale_id]['products'][] = [
                        'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                        'product_no' => $saleItem->product->upc,
                        'promoters' => $allPromoters,
                        'receipt_data' => $saleItem->product->name,
                        'qty' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                        'gross_sales' => CommonFunctions::currencyFormat((float) $grossSales),
                        'discount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                        'net_sales_exclusive_tax' => CommonFunctions::currencyFormat(
                            (float) ($saleItem->total_price_paid - $saleItem->total_tax_amount)
                        ),
                        'tax_amount' => CommonFunctions::currencyFormat((float) $saleItem->total_tax_amount),
                        'net_sales_inclusive_tax' => CommonFunctions::currencyFormat(
                            (float) $saleItem->total_price_paid
                        ),
                    ];
                }
            }

            $total['gross_sales'] = CommonFunctions::currencyFormat((float) $total['gross_sales']);
            $total['discount'] = CommonFunctions::currencyFormat((float) $total['discount']);
            $total['net_sales_exclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_exclusive_tax']
            );
            $total['tax_amount'] = CommonFunctions::currencyFormat((float) $total['tax_amount']);
            $total['net_sales_inclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_inclusive_tax']
            );

            $locationSales[]['sale'] = $total;

            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $columns = [
            'Counter Name',
            'Product No',
            'Receipt Data',
            'Qty',
            'Gross Sales',
            'Discount',
            'Net Sales Exclusive Tax',
            'Tax amount',
            'Net Sales Inclusive Tax',
        ];

        return [$locationSalesData, $columns];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function preparedGeneralSalesByColorAndSize(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getForGeneralSalesReportBySalesDateColorAndSize(
            $filterData,
            $excludeProductsWithNoPrice
        );

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationSaleData = [];
        foreach ($locations as $location) {
            $total = [
                'counter_name' => 'Total',
                'product_no' => '',
                'promoters' => '',
                'description' => '',
                'qty' => 0,
                'gross_sales' => 0,
                'discount' => 0,
                'net_sales' => 0,
            ];

            $locationSaleData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];
            $articleNumberWiseSaleItems = $saleItems->where(
                'sale.counterUpdate.counter.location_id',
                $location->id
            )->groupBy('product.article_number');

            $locationSales = [];
            $articleNumberWiseLocationSales = [];
            foreach ($articleNumberWiseSaleItems as $articleNumber => $saleItems) {
                if (! $articleNumber) {
                    foreach ($saleItems as $saleItem) {
                        $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);
                        $netSales = ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                        $total['qty'] += $saleItem->quantity;
                        $total['gross_sales'] += $grossSales;
                        $total['discount'] += $saleItem->total_discount_amount;
                        $total['net_sales'] += $netSales;

                        $promoters = $saleItem->promoters;
                        $allPromoters = [];

                        foreach ($promoters as $promoter) {
                            $employee = $promoter->employee;
                            $allPromoters[] = $employee->getFullName();
                        }

                        $allPromoters = implode(',', $allPromoters);

                        $articleNumberWiseLocationSales[] = [
                            'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                            'product_no' => $saleItem->product->upc,
                            'promoters' => $allPromoters,
                            'description' => $saleItem->product->name,
                            'qty' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                            'gross_sales' => CommonFunctions::currencyFormat((float) $grossSales),
                            'discount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                            'net_sales' => CommonFunctions::currencyFormat((float) $netSales),
                        ];
                    }
                }

                if ($articleNumber) {
                    foreach ($saleItems as $saleItem) {
                        $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);
                        $netSales = ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                        $total['qty'] += $saleItem->quantity;
                        $total['gross_sales'] += $grossSales;
                        $total['discount'] += $saleItem->total_discount_amount;
                        $total['net_sales'] += $netSales;

                        $promoters = $saleItem->promoters;
                        $allPromoters = [];
                        foreach ($promoters as $promoter) {
                            $employee = $promoter->employee;
                            $allPromoters[] = $employee->getFullName();
                        }

                        $allPromoters = implode(',', $allPromoters);

                        if (! array_key_exists($articleNumber, $locationSales)) {
                            $locationSales[$articleNumber]['product'] = [
                                'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                                'product_no' => $saleItem->product->article_number,
                                'promoters' => $allPromoters,
                                'description' => $saleItem->product->name,
                                'qty' => 0,
                                'gross_sales' => 0,
                                'discount' => 0,
                                'net_sales' => 0,
                            ];
                        }

                        $locationSales[$articleNumber]['product']['qty'] += $saleItem->quantity;
                        $locationSales[$articleNumber]['product']['gross_sales'] += $grossSales;
                        $locationSales[$articleNumber]['product']['discount'] += $saleItem->total_discount_amount;
                        $locationSales[$articleNumber]['product']['net_sales'] += $netSales;

                        if (! array_key_exists('sales', $locationSales[$articleNumber])) {
                            $locationSales[$articleNumber]['sales'] = [];
                        }

                        if (! array_key_exists($saleItem->product->color_id, $locationSales[$articleNumber]['sales'])
                        ) {
                            $locationSales[$articleNumber]['sales'][$saleItem->product->color_id][$saleItem->product->size_id] = [
                                'product_no' => $saleItem->product->upc,
                                'color' => $saleItem->product->color?->name,
                                'size' => $saleItem->product->size?->name,
                                'qty' => 0,
                            ];
                        }

                        if (! array_key_exists(
                            $saleItem->product->size_id,
                            $locationSales[$articleNumber]['sales'][$saleItem->product->color_id]
                        )
                        ) {
                            $locationSales[$articleNumber]['sales'][$saleItem->product->color_id][$saleItem->product->size_id] = [
                                'product_no' => $saleItem->product->upc,
                                'color' => $saleItem->product->color?->name,
                                'size' => $saleItem->product->size?->name,
                                'qty' => 0,
                            ];
                        }

                        $locationSales[$articleNumber]['sales'][$saleItem->product->color_id][$saleItem->product->size_id]['qty'] += $saleItem->quantity;
                    }
                }
            }

            foreach ($articleNumberWiseLocationSales as $articleNumberWiseLocationSale) {
                $locationSales[]['product'] = $articleNumberWiseLocationSale;
            }

            $total['gross_sales'] = CommonFunctions::currencyFormat((float) $total['gross_sales']);
            $total['discount'] = CommonFunctions::currencyFormat((float) $total['discount']);
            $total['net_sales'] = CommonFunctions::currencyFormat((float) $total['net_sales']);

            $locationSales[]['product'] = $total;

            $locationSaleData[$location->id]['data'] = $locationSales;
        }

        return $locationSaleData;
    }

    private function preparedGeneralSalesByAttribute(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getForGeneralSalesReportBySalesDateAttribute(
            $filterData,
            $excludeProductsWithNoPrice
        );

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationSaleData = [];
        foreach ($locations as $location) {
            $total = [
                'counter_name' => 'Total',
                'product_no' => '',
                'promoters' => '',
                'description' => '',
                'qty' => 0,
                'gross_sales' => 0,
                'discount' => 0,
                'net_sales' => 0,
            ];

            $locationSaleData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];
            $articleNumberWiseSaleItems = $saleItems->where(
                'sale.counterUpdate.counter.location_id',
                $location->id
            )->groupBy('product.master_product_id');

            $locationSales = [];
            $articleNumberWiseLocationSales = [];
            foreach ($articleNumberWiseSaleItems as $articleNumber => $saleItems) {
                if (! $articleNumber) {
                    foreach ($saleItems as $saleItem) {
                        $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);
                        $netSales = ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                        $total['qty'] += $saleItem->quantity;
                        $total['gross_sales'] += $grossSales;
                        $total['discount'] += $saleItem->total_discount_amount;
                        $total['net_sales'] += $netSales;

                        $promoters = $saleItem->promoters;
                        $allPromoters = [];

                        foreach ($promoters as $promoter) {
                            $employee = $promoter->employee;
                            $allPromoters[] = $employee->getFullName();
                        }

                        $allPromoters = implode(',', $allPromoters);

                        $articleNumberWiseLocationSales[] = [
                            'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                            'product_no' => $saleItem->product->upc,
                            'promoters' => $allPromoters,
                            'description' => $saleItem->product->name,
                            'qty' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                            'gross_sales' => CommonFunctions::currencyFormat((float) $grossSales),
                            'discount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                            'net_sales' => CommonFunctions::currencyFormat((float) $netSales),
                        ];
                    }
                }

                if ($articleNumber) {
                    foreach ($saleItems as $saleItem) {
                        $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);
                        $netSales = ($saleItem->total_price_paid - $saleItem->total_tax_amount);

                        $total['qty'] += $saleItem->quantity;
                        $total['gross_sales'] += $grossSales;
                        $total['discount'] += $saleItem->total_discount_amount;
                        $total['net_sales'] += $netSales;

                        $promoters = $saleItem->promoters;
                        $allPromoters = [];
                        foreach ($promoters as $promoter) {
                            $employee = $promoter->employee;
                            $allPromoters[] = $employee->getFullName();
                        }

                        $allPromoters = implode(',', $allPromoters);

                        if (! array_key_exists($articleNumber, $locationSales)) {
                            $locationSales[$articleNumber]['product'] = [
                                'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                                'product_no' => $saleItem->product->article_number,
                                'promoters' => $allPromoters,
                                'description' => $saleItem->product->name,
                                'qty' => 0,
                                'gross_sales' => 0,
                                'discount' => 0,
                                'net_sales' => 0,
                            ];
                        }

                        $locationSales[$articleNumber]['product']['qty'] += $saleItem->quantity;
                        $locationSales[$articleNumber]['product']['gross_sales'] += $grossSales;
                        $locationSales[$articleNumber]['product']['discount'] += $saleItem->total_discount_amount;
                        $locationSales[$articleNumber]['product']['net_sales'] += $netSales;

                        if (! array_key_exists('sales', $locationSales[$articleNumber])) {
                            $locationSales[$articleNumber]['sales'] = [];
                        }

                        if (! empty($saleItem->product->productVariantValues)) {
                            $currentLevel = &$locationSales[$articleNumber]['sales'];
                            $attributeString = '';

                            $variantValues = $saleItem->product->productVariantValues;
                            $lastIndex = count($variantValues) - 1;

                            foreach ($variantValues as $index => $variantValue) {
                                $attributeName = $variantValue->attribute->name;
                                $attributeValue = $variantValue->value;

                                if (! isset($currentLevel[$attributeValue])) {
                                    if ($index === $lastIndex) {
                                        $attributeString .= $attributeName . ' : ' . $attributeValue;

                                        $currentLevel[$attributeValue] = [
                                            /* @phpstan-ignore-next-line */
                                            'product_no' => $saleItem->product->upc,
                                            $attributeName => $attributeValue,
                                            'attributeString' => $attributeString,
                                            'qty' => 0,
                                        ];
                                    } else {
                                        $currentLevel[$attributeValue] = [];
                                    }
                                }

                                $attributeString .= $attributeName . ' : ' . $attributeValue . ', ';
                                $currentLevel = &$currentLevel[$attributeValue];
                            }

                            /* @phpstan-ignore-next-line */
                            $currentLevel['qty'] += $saleItem->quantity;
                        }
                    }
                }
            }

            foreach ($articleNumberWiseLocationSales as $articleNumberWiseLocationSale) {
                $locationSales[]['product'] = $articleNumberWiseLocationSale;
            }

            $total['gross_sales'] = CommonFunctions::currencyFormat((float) $total['gross_sales']);
            $total['discount'] = CommonFunctions::currencyFormat((float) $total['discount']);
            $total['net_sales'] = CommonFunctions::currencyFormat((float) $total['net_sales']);

            $locationSales[]['product'] = $total;

            $locationSaleData[$location->id]['data'] = $locationSales;
        }

        return $locationSaleData;
    }

    /**
     * @return array<int, array<string|array{product_no: mixed, receipt_data: mixed, qty: mixed, gross_sales: string|float|int, discount: mixed, net_sales_exclusive_tax: string|float|int, tax_amount: mixed, net_sales_inclusive_tax: mixed}>>
     */
    private function preparedGeneralSalesByReceiptAndItemForExport(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getForGeneralSalesReportBySalesDate($filterData, $excludeProductsWithNoPrice);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationSalesData = [];

        foreach ($locations as $location) {
            $total = [
                'counter_name' => 'Total',
                'product_no' => '',
                'receipt_data' => '',
                'qty' => 0,
                'gross_sales' => 0,
                'discount' => 0,
                'net_sales_exclusive_tax' => 0,
                'tax_amount' => 0,
                'net_sales_inclusive_tax' => 0,
            ];
            $groupSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id)->groupBy(
                'sale_id'
            );
            $locationSales = [];

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($groupSaleItems as $groupSaleItem) {
                $saleItem = $groupSaleItem->first();

                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleItem->sale->happened_at);
                $happenedAt = $happenedAtFormat->format('d M Y h:i:s A');

                $locationSales[$saleItem->sale_id]['sale'] = [
                    'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                    'product_no' => $saleItem->sale->offline_sale_id,
                    'receipt_data' => $happenedAt,
                    'qty' => '',
                    'gross_sales' => '',
                    'discount' => '',
                    'net_sales_exclusive_tax' => '',
                    'tax_amount' => '',
                    'net_sales_inclusive_tax' => '',
                ];

                foreach ($groupSaleItem->sortByDesc('sale.happened_at') as $saleItem) {
                    $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);

                    $total['qty'] += $saleItem->quantity;
                    $total['gross_sales'] += $grossSales;
                    $total['discount'] += $saleItem->total_discount_amount;
                    $total['net_sales_exclusive_tax'] += $saleItem->total_price_paid - $saleItem->total_tax_amount;
                    $total['tax_amount'] += $saleItem->total_tax_amount;
                    $total['net_sales_inclusive_tax'] += $saleItem->total_price_paid;

                    $promoters = $saleItem->promoters;
                    $allPromoters = [];

                    foreach ($promoters as $promoter) {
                        $employee = $promoter->employee;
                        $allPromoters[] = $employee->getFullName();
                    }

                    $allPromoters = implode(',', $allPromoters);

                    $locationSales[$saleItem->sale_id]['products'][] = [
                        'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                        'product_no' => $saleItem->product->upc,
                        'promoters' => $allPromoters,
                        'receipt_data' => $saleItem->product->name,
                        'qty' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                        'gross_sales' => CommonFunctions::currencyFormat((float) $grossSales),
                        'discount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                        'net_sales_exclusive_tax' => CommonFunctions::currencyFormat(
                            (float) ($saleItem->total_price_paid - $saleItem->total_tax_amount)
                        ),
                        'tax_amount' => CommonFunctions::currencyFormat((float) $saleItem->total_tax_amount),
                        'net_sales_inclusive_tax' => CommonFunctions::currencyFormat(
                            (float) $saleItem->total_price_paid
                        ),
                    ];
                }
            }

            $total['gross_sales'] = CommonFunctions::currencyFormat((float) $total['gross_sales']);
            $total['discount'] = CommonFunctions::currencyFormat((float) $total['discount']);
            $total['net_sales_exclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_exclusive_tax']
            );
            $total['tax_amount'] = CommonFunctions::currencyFormat((float) $total['tax_amount']);
            $total['net_sales_inclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_inclusive_tax']
            );

            $locationSales[]['sale'] = $total;
            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $columns = [
            'Counter Name',
            'Product No',
            'Promoter',
            'Receipt Data',
            'Qty',
            'Gross Sales',
            'Discount',
            'Net Sales Exclusive Tax',
            'Tax amount',
            'Net Sales Inclusive Tax',
        ];

        return [$locationSalesData, $columns, $dateRange];
    }

    private function preparedGeneralSalesByProduct(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getGeneralSalesReportByProduct($filterData, $excludeProductsWithNoPrice);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);
        $productService = resolve(ProductService::class);
        $locationsSales = [];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales' => [],
                'products' => [],
                'totals' => [
                    'totalQuantity' => 0,
                    'totalGrossSales' => 0,
                    'totalDiscountAmount' => 0,
                    'totalNetSaleExclusiveTax' => 0,
                    'totalNetSaleInclusiveTax' => 0,
                    'totalTaxAmount' => 0,
                ],
            ];

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($saleItems->where('sale.counterUpdate.counter.location_id', $location->id) as $saleItem) {
                $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);

                $locationSales['totals']['totalQuantity'] += $saleItem->quantity;
                $locationSales['totals']['totalGrossSales'] += $grossSales;
                $locationSales['totals']['totalDiscountAmount'] += $saleItem->total_discount_amount;
                $locationSales['totals']['totalNetSaleExclusiveTax'] += $saleItem->total_price_paid - $saleItem->total_tax_amount;
                $locationSales['totals']['totalNetSaleInclusiveTax'] += $saleItem->total_price_paid;
                $locationSales['totals']['totalTaxAmount'] += $saleItem->total_tax_amount;

                $promoters = $saleItem->promoters;
                $allPromoters = [];

                /** @var Brand|null $brand */
                $brand = config(
                    'app.product_variant'
                ) ? $saleItem->product?->masterProduct?->brand?->name : $saleItem->product->brand?->name;

                $colorSizeOrAttributeData = [];
                if (config('app.product_variant')) {
                    $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint(
                        $saleItem->product
                    );
                } else {
                    $colorSizeOrAttributeData = [
                        'color' => $saleItem->product->color?->name ?? 'N/A',
                        'size' => $saleItem->product->size?->name ?? 'N/A',
                    ];
                }

                foreach ($promoters as $promoter) {
                    $employee = $promoter->employee;
                    $allPromoters[] = $employee->getFullName();
                }

                $allPromoters = implode(',', $allPromoters);

                $locationSales['products'][] = [
                    'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                    'product_upc' => $saleItem->product->upc,
                    'product_name' => $saleItem->product->name,
                    'promoter' => $allPromoters,
                    'brand' => $brand,
                    ...$colorSizeOrAttributeData,
                    'quantity' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                    'gross_sales_exclusive_tax' => CommonFunctions::currencyFormat((float) $grossSales),
                    'discount_amount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                    'net_sales_exclusive_tax' => CommonFunctions::currencyFormat(
                        (float) ($saleItem->total_price_paid - $saleItem->total_tax_amount)
                    ),
                    'tax_amount' => CommonFunctions::currencyFormat((float) $saleItem->total_tax_amount),
                    'net_sales_inclusive_tax' => CommonFunctions::currencyFormat((float) $saleItem->total_price_paid),
                ];
            }

            $locationsSales[] = $locationSales;

            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $columns = [
            'Counter Name',
            'Product UPC',
            'Product Name',
            'Promoter',
            'Brand',
            ...config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'],
            'Quantity',
            'Gross Sales Exclusive Tax',
            'Discount amount',
            'Net Sales Exclusive Tax',
            'Tax amount',
            'Net Sales Inclusive Tax',
        ];

        return [$locationsSales, $columns];
    }

    private function preparedGeneralSalesBySummary(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getGeneralSalesReportBySummary($filterData, $excludeProductsWithNoPrice);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationsSales = [];
        $grandTotal = [
            'totalItemSold' => 0,
            'totalSalesAmount' => 0,
        ];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'products' => [],
                'totals' => [
                    'totalItemSold' => 0,
                    'totalSalesAmount' => 0,
                ],
            ];

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($saleItems->sortBy('sale.happened_at')->groupBy(
                ['sale.counterUpdate.counter.location_id', 'sale_date']
            ) as $locationId => $saleItemWithLocations) {
                foreach ($saleItemWithLocations as $key => $saleItem) {
                    if ($locationId === $location->id) {
                        $locationSales['totals']['totalItemSold'] += $saleItem->sum('total_quantity');
                        $locationSales['totals']['totalSalesAmount'] += $saleItem->sum('total_price_paid');
                        $grandTotal['totalItemSold'] += $saleItem->sum('total_quantity');
                        $grandTotal['totalSalesAmount'] += $saleItem->sum('total_price_paid');

                        $locationSales['products'][] = [
                            'date' => $key,
                            'items' => $saleItem->sum('total_quantity'),
                            'sales' => $saleItem->sum('total_price_paid'),
                        ];
                    }
                }
            }

            $locationsSales[] = $locationSales;
            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $locationsSales['grand_total'] = $grandTotal;

        $columns = ['Date', 'Items', 'Sales'];

        return [$locationsSales, $columns];
    }

    private function preparedGeneralSalesByDateAndBrand(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $saleItems = $saleItemQueries->getGeneralSalesReportByDateAndBrand($filterData, $excludeProductsWithNoPrice);
        $locationsArray = $locationQueries->getByIdsWithNameAndCode(
            $company->id,
            $filterData['location_ids'],
        )->toArray();

        $brandLocationsSales = [];
        $columns = [
            0 => 'Location Name',
        ];
        $grandTotal = [
            'location_name' => 'Grand Total',
            'total' => 0,
        ];

        $totals = [];
        foreach ($saleItems->sortBy('sale.happened_at')->groupBy(
            [
                config('app.product_variant') ? 'product.masterProduct.brand.id' : 'product.brand.id',
                'sale.counterUpdate.counter.location_id',
                'sale_date',
            ]
        ) as $brandId => $saleItemsWithBrand) {
            $totals[$brandId] = [
                'location_name' => 'Total',
                'total' => 0,
            ];
            $brand = $brandQueries->getById($brandId);

            $brandLocationsSales[$brandId] = [
                'brand_name' => $brand->name,
                'brand_total' => 0,
                'locations' => [],
            ];

            $locations = [];
            foreach ($saleItemsWithBrand as $locationId => $saleItemsWithLocation) {
                $locationName = '';
                foreach ($locationsArray as $location) {
                    if ($location['id'] == $locationId) {
                        $locationName = $location['name'];
                        break;
                    }
                }

                $locations[$locationId] = [
                    'location_name' => $locationName,
                    'total' => 0,
                ];

                foreach ($saleItemsWithLocation as $date => $saleItems) {
                    /** @var Carbon $dateFormat */
                    $dateFormat = Carbon::createFromFormat('Y-m-d', $date);
                    $columns[$date] = $dateFormat->format('d/m/Y');

                    $locations[$locationId]['total'] += $saleItems->sum('total_price_paid');

                    $totals[$brandId]['total'] += $saleItems->sum('total_price_paid');

                    $locations[$locationId][$date] = $saleItems->sum('total_price_paid');

                    if (array_key_exists($date, $totals[$brandId])) {
                        $totals[$brandId][$date] += $saleItems->sum('total_price_paid');
                    } else {
                        $totals[$brandId][$date] = $saleItems->sum('total_price_paid');
                    }

                    if (array_key_exists($date, $grandTotal)) {
                        $grandTotal[$date] += $saleItems->sum('total_price_paid');
                    } else {
                        $grandTotal[$date] = $saleItems->sum('total_price_paid');
                    }
                }
            }

            $grandTotal['total'] += $totals[$brandId]['total'];
            $brandLocationsSales[$brandId]['locations'] = $locations;
        }

        foreach ($totals as $brandId => $total) {
            $brandLocationsSales[$brandId]['locations'][] = $total;
        }

        $columns[] = 'Total';

        return [$brandLocationsSales, $grandTotal, $columns];
    }

    private function preparedGeneralSalesByPromoter(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getGeneralSalesReportBySummary($filterData, $excludeProductsWithNoPrice);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationsSales = [];
        $grandTotal = [
            'totalItemSold' => 0,
            'totalSalesAmount' => 0,
        ];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'products' => [],
            ];

            $products = collect([]);
            $locationItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id);
            foreach ($locationItems->sortBy('sale_date')->groupBy('sale_date') as $dateWiseLocationItems) {
                $promoterData = [];
                foreach ($dateWiseLocationItems as $dateWiseLocationItem) {
                    foreach ($dateWiseLocationItem->promoters as $promoter) {
                        $employee = $promoter->employee;
                        $promoterId = $employee->id;
                        $promoterName = $employee->getFullName();

                        if (! isset($promoterData[$promoterId])) {
                            $promoterData[$promoterId] = [
                                'date' => $dateWiseLocationItem->sale_date,
                                'promoter' => $promoterName,
                                'items' => 0,
                                'sales' => 0,
                            ];
                        }

                        $grandTotal['totalItemSold'] += $dateWiseLocationItem->total_quantity;
                        $grandTotal['totalSalesAmount'] += $dateWiseLocationItem->total_price_paid;

                        $promoterData[$promoterId]['items'] += $dateWiseLocationItem->total_quantity;
                        $promoterData[$promoterId]['sales'] += $dateWiseLocationItem->total_price_paid;
                    }
                }

                $products = $products->merge($promoterData);
            }

            $products->push([
                'date' => 'Total',
                'promoter' => '',
                'items' => $products->sum('items'),
                'sales' => $products->sum('sales'),
            ]);

            $locationSales['products'] = $products->toArray();
            $locationsSales['data'][] = $locationSales;
        }

        $locationsSales['grand_total'] = $grandTotal;
        $columns = ['Date', 'Promoter', 'Items', 'Sales'];

        return [$locationsSales, $columns];
    }

    /**
     * @return array<int, array<string|array{product_no: mixed, description: mixed, qty: mixed, gross_sales: string|float|int, discount: mixed, net_sales_exclusive_tax: string|float|int, tax_amount: mixed, net_sales_inclusive_tax: mixed}>>
     */
    private function preparedGeneralSalesByItemAndReceiptForExport(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getForGeneralSalesReportBySalesDate($filterData, $excludeProductsWithNoPrice);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationSalesData = [];

        foreach ($locations as $location) {
            $total = [
                'counter_name' => 'Total',
                'product_no' => '',
                'description' => '',
                'qty' => 0,
                'gross_sales' => 0,
                'discount' => 0,
                'net_sales_exclusive_tax' => 0,
                'tax_amount' => 0,
                'net_sales_inclusive_tax' => 0,
            ];
            $locationSales = [];

            $groupSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id)->groupBy(
                'product_id'
            );

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($groupSaleItems as $groupSaleItem) {
                $saleItem = $groupSaleItem->first();

                $locationSales[$saleItem->product_id]['product'] = [
                    'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                    'product_no' => $saleItem->product->upc,
                    'description' => $saleItem->product->name,
                    'qty' => '',
                    'gross_sales' => '',
                    'discount' => '',
                    'net_sales_exclusive_tax' => '',
                    'tax_amount' => '',
                    'net_sales_inclusive_tax' => '',
                ];
                foreach ($groupSaleItem->sortByDesc('sale.happened_at') as $saleItem) {
                    $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);

                    $promoters = $saleItem->promoters;
                    $allPromoters = [];

                    foreach ($promoters as $promoter) {
                        $employee = $promoter->employee;
                        $allPromoters[] = $employee->getFullName();
                    }

                    $allPromoters = implode(',', $allPromoters);

                    $total['qty'] += $saleItem->quantity;
                    $total['gross_sales'] += $grossSales;
                    $total['discount'] += $saleItem->total_discount_amount;
                    $total['net_sales_exclusive_tax'] += $saleItem->total_price_paid - $saleItem->total_tax_amount;
                    $total['tax_amount'] += $saleItem->total_tax_amount;
                    $total['net_sales_inclusive_tax'] += $saleItem->total_price_paid;

                    /** @var Carbon $happenedAtFormat */
                    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleItem->sale->happened_at);
                    $happenedAt = $happenedAtFormat->format('d M Y h:i:s A');

                    $locationSales[$saleItem->product_id]['sales'][] = [
                        'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                        'product_no' => $happenedAt,
                        'promoters' => $allPromoters,
                        'description' => $saleItem->sale->offline_sale_id,
                        'qty' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                        'gross_sales' => CommonFunctions::currencyFormat((float) $grossSales),
                        'discount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                        'net_sales_exclusive_tax' => CommonFunctions::currencyFormat(
                            (float) ($saleItem->total_price_paid - $saleItem->total_tax_amount)
                        ),
                        'tax_amount' => CommonFunctions::currencyFormat((float) $saleItem->total_tax_amount),
                        'net_sales_inclusive_tax' => CommonFunctions::currencyFormat(
                            (float) $saleItem->total_price_paid
                        ),
                    ];
                }
            }

            $total['gross_sales'] = CommonFunctions::currencyFormat((float) $total['gross_sales']);
            $total['discount'] = CommonFunctions::currencyFormat((float) $total['discount']);
            $total['net_sales_exclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_exclusive_tax']
            );
            $total['tax_amount'] = CommonFunctions::currencyFormat((float) $total['tax_amount']);
            $total['net_sales_inclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_inclusive_tax']
            );

            $locationSales[]['product'] = $total;

            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $columns = [
            'Product No',
            'Promoter',
            'Description',
            'Qty',
            'Gross Sales',
            'Discount',
            'Net Sales Exclusive Tax',
            'Tax amount',
            'Net Sales Inclusive Tax',
        ];

        return [$locationSalesData, $columns, $dateRange];
    }

    /**
     * @return array<int, mixed>
     */
    private function preparedGeneralSalesByItemAndReceipt(
        array $filterData,
        Company $company,
        bool $excludeProductsWithNoPrice
    ): array {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getForGeneralSalesReportBySalesDate($filterData, $excludeProductsWithNoPrice);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $locationSalesData = [];

        foreach ($locations as $location) {
            $locationSales = [];

            $total = [
                'counter_name' => 'Total',
                'product_no' => '',
                'description' => '',
                'qty' => 0,
                'gross_sales' => 0,
                'discount' => 0,
                'net_sales_exclusive_tax' => 0,
                'tax_amount' => 0,
                'net_sales_inclusive_tax' => 0,
            ];
            $groupSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id)->sortBy(
                'sale.happened_at'
            )->groupBy('product_id');

            $locationSalesData[$location->id] = [
                'location_name' => $location->name . ' [' . $location->code . ']',
            ];

            foreach ($groupSaleItems as $groupSaleItem) {
                $saleItem = $groupSaleItem->first();

                $locationSales[$saleItem->product_id]['product'] = [
                    'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                    'product_no' => $saleItem->product->upc,
                    'description' => $saleItem->product->name,
                    'qty' => '',
                    'gross_sales' => '',
                    'discount' => '',
                    'net_sales_exclusive_tax' => '',
                    'tax_amount' => '',
                    'net_sales_inclusive_tax' => '',
                ];

                foreach ($groupSaleItem->sortBy('sale.happened_at') as $saleItem) {
                    $promoters = $saleItem->promoters;
                    $allPromoters = [];

                    foreach ($promoters as $promoter) {
                        $employee = $promoter->employee;
                        $allPromoters[] = $employee->getFullName();
                    }

                    $allPromoters = implode(',', $allPromoters);

                    $grossSales = ($saleItem->original_price_per_unit * $saleItem->quantity);

                    $total['qty'] += $saleItem->quantity;
                    $total['gross_sales'] += $grossSales;
                    $total['discount'] += $saleItem->total_discount_amount;
                    $total['net_sales_exclusive_tax'] += $saleItem->total_price_paid - $saleItem->total_tax_amount;
                    $total['tax_amount'] += $saleItem->total_tax_amount;
                    $total['net_sales_inclusive_tax'] += $saleItem->total_price_paid;

                    /** @var Carbon $happenedAtFormat */
                    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $saleItem->sale->happened_at);
                    $happenedAt = $happenedAtFormat->format('d M Y h:i:s A');

                    $locationSales[$saleItem->product_id]['sales'][] = [
                        'counter_name' => $saleItem->sale->counterUpdate->counter->name,
                        'product_no' => $happenedAt,
                        'promoters' => $allPromoters,
                        'description' => $saleItem->sale->offline_sale_id,
                        'qty' => CommonFunctions::truncateDecimal((float) $saleItem->quantity),
                        'gross_sales' => CommonFunctions::currencyFormat((float) $grossSales),
                        'discount' => CommonFunctions::currencyFormat((float) $saleItem->total_discount_amount),
                        'net_sales_exclusive_tax' => CommonFunctions::currencyFormat(
                            (float) ($saleItem->total_price_paid - $saleItem->total_tax_amount)
                        ),
                        'tax_amount' => CommonFunctions::currencyFormat((float) $saleItem->total_tax_amount),
                        'net_sales_inclusive_tax' => CommonFunctions::currencyFormat(
                            (float) $saleItem->total_price_paid
                        ),
                    ];
                }
            }

            $total['gross_sales'] = CommonFunctions::currencyFormat((float) $total['gross_sales']);
            $total['discount'] = CommonFunctions::currencyFormat((float) $total['discount']);
            $total['net_sales_exclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_exclusive_tax']
            );
            $total['tax_amount'] = CommonFunctions::currencyFormat((float) $total['tax_amount']);
            $total['net_sales_inclusive_tax'] = CommonFunctions::currencyFormat(
                (float) $total['net_sales_inclusive_tax']
            );

            $locationSales[]['product'] = $total;

            $locationSalesData[$location->id]['data'] = $locationSales;
        }

        $columns = [
            'Counter Name',
            'Product No',
            'Description',
            'Qty',
            'Gross Sales',
            'Discount',
            'Net Sales Exclusive Tax',
            'Tax amount',
            'Net Sales Inclusive Tax',
        ];

        return [$locationSalesData, $columns];
    }

    private function preparedGeneralSalesByCurrentDayVsPreviousDay(
        Company $company,
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): array {
        $brandQueries = resolve(BrandQueries::class);
        $brandWiseSales = $brandQueries->getSalesRecordsGroupedByBrandAndRegion(
            $filterData,
            $company->getKey(),
            $excludeProductsWithNoPrice
        );

        $records = [];
        $columns = [
            'location_name' => '',
        ];

        /** @var Carbon $filterDateCarbon */
        $filterDateCarbon = Carbon::createFromFormat('Y-m-d', $filterData['date']);

        $mainColumns = [
            'location' => 'Location (code)',
            $filterDateCarbon->format('d-M-Y') => $filterDateCarbon->format('d-M'),
            'Sales As At Yesterday' => 'Sales As At Yesterday',
            '% As At Yesterday' => '% As At Yesterday',
        ];

        /** @var array $grandTotal */
        $grandTotal = [
            'location_name' => 'Grand Total',
            'year_comparison' => [],
        ];

        $previousDates = [];
        $yearComparison = [];

        foreach ($brandWiseSales as $brandName => $regionWiseSales) {
            foreach ($regionWiseSales as $regionName => $dateWiseSales) {
                $total = [
                    'region_name' => $regionName,
                ];

                foreach ($dateWiseSales as $date => $sales) {
                    if (! array_key_exists($date, $columns) && $filterDateCarbon->format('d-m-Y') === $date) {
                        $columns[$date] = $date;
                    }

                    foreach ($sales as $sale) {
                        if (! array_key_exists($brandName, $records)) {
                            $records[$brandName] = [];
                        }

                        if (! array_key_exists($regionName, $records[$brandName])) {
                            $records[$brandName][$regionName] = [];
                        }

                        if (! array_key_exists($sale->code, $records[$brandName][$regionName])) {
                            $records[$brandName][$regionName][$sale->code] = [];
                        }

                        if (! array_key_exists(
                            $date,
                            $records[$brandName][$regionName][$sale->code]
                        ) && $filterDateCarbon->format('d-m-Y') === $date) {
                            $records[$brandName][$regionName][$sale->code][$date] = 0;
                        }

                        if (! array_key_exists($date, $total) && $filterDateCarbon->format('d-m-Y') === $date) {
                            $total[$date] = 0;
                        }

                        if (! array_key_exists('previous_date', $total)) {
                            $total['previous_date'] = [];
                        }

                        if (! array_key_exists($date, $total['previous_date']) && $filterDateCarbon->format(
                            'd-m-Y'
                        ) !== $date) {
                            $total['previous_date'][$date] = 0;
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

                        if (! array_key_exists(
                            $filterDateCarbon->format('d-m-Y'),
                            $records[$brandName][$regionName][$sale->code]
                        )) {
                            $records[$brandName][$regionName][$sale->code][$filterDateCarbon->format('d-m-Y')] = 0;
                        }

                        if (! array_key_exists($filterDateCarbon->format('d-m-Y'), $total)) {
                            $total[$filterDateCarbon->format('d-m-Y')] = 0;
                        }

                        if (! array_key_exists($filterDateCarbon->format('d-m-Y'), $grandTotal)) {
                            $grandTotal[$filterDateCarbon->format('d-m-Y')] = 0;
                        }

                        $records[$brandName][$regionName][$sale->code]['location_name'] = $sale->location_name . '( ' . $sale->code . ' )';

                        if (! array_key_exists('previous_date', $records[$brandName][$regionName][$sale->code])) {
                            $records[$brandName][$regionName][$sale->code]['previous_date'] = [];
                        }

                        if (! array_key_exists(
                            $date,
                            $records[$brandName][$regionName][$sale->code]['previous_date']
                        ) && $filterDateCarbon->format('d-m-Y') !== $date) {
                            $records[$brandName][$regionName][$sale->code]['previous_date'][$date] = 0;
                        }

                        if ($filterDateCarbon->format('d-m-Y') !== $date) {
                            $records[$brandName][$regionName][$sale->code]['previous_date'][$date] += $sale->total_price_paid;
                            $total['previous_date'][$date] += $sale->total_price_paid;
                            $grandTotal['previous_date'][$date] += $sale->total_price_paid;
                            if (! in_array($date, $previousDates)) {
                                $previousDates[] = $date;
                            }
                        } else {
                            $records[$brandName][$regionName][$sale->code][$date] += $sale->total_price_paid;
                            $total[$date] += $sale->total_price_paid;
                            $grandTotal[$date] += $sale->total_price_paid;
                        }
                    }
                }

                $records[$brandName][$regionName]['total'] = $total;
            }
        }

        $preparedSales = [];
        /** @var Carbon $yesterdayDateCarbon */
        $yesterdayDateCarbon = Carbon::createFromFormat('Y-m-d', $filterData['date']);
        $yesterdayDate = $yesterdayDateCarbon->subDays();
        $key = 0;
        $totalRecordsCounts = 1;

        foreach ($records as $brandName => $brandWiseRecords) {
            foreach ($brandWiseRecords as $regionWiseRecords) {
                foreach ($regionWiseRecords as $locationWiseRecords) {
                    foreach ($previousDates as $previousDate) {
                        if (! array_key_exists($previousDate, $locationWiseRecords['previous_date'])) {
                            $locationWiseRecords['previous_date'][$previousDate] = 0;
                        }
                    }

                    $preparedSales[$brandName][$key] = $locationWiseRecords;

                    $thisYearSales = 0;
                    if (array_key_exists($yesterdayDate->format('d-m-Y'), $locationWiseRecords['previous_date'])) {
                        $thisYearSales = (float) $locationWiseRecords['previous_date'][$yesterdayDate->format('d-m-Y')];
                    }

                    $totalRecordsCounts = count($locationWiseRecords['previous_date']) > 1 ? count(
                        $locationWiseRecords['previous_date']
                    ) - 1 : count($locationWiseRecords['previous_date']);

                    $lastYearTodayDate = $yesterdayDate->copy();
                    for ($i = 0; $i < $totalRecordsCounts; $i++) {
                        $lastYearTodayDate = $lastYearTodayDate->subYear();
                        $lastYearSaleAvg = 0;
                        if (array_key_exists(
                            $lastYearTodayDate->format('d-m-Y'),
                            $locationWiseRecords['previous_date']
                        )) {
                            $lastYeasSales = (float) $locationWiseRecords['previous_date'][$lastYearTodayDate->format(
                                'd-m-Y'
                            )];
                            if ($lastYeasSales > 0) {
                                $lastYearSaleAvg = CommonFunctions::numberFormat(
                                    (($thisYearSales - $lastYeasSales) / $lastYeasSales) * 100
                                );
                            }
                        }

                        $columnKey = $yesterdayDate->format('Y') . '-' . $lastYearTodayDate->format('Y');
                        $preparedSales[$brandName][$key]['year_comparison'][$columnKey] = $lastYearSaleAvg;
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
        }

        $columns = collect($columns)->sortKeysDesc()->toArray();
        $preparedSalesKeys = current($preparedSales);
        if (is_array($preparedSalesKeys)) {
            $preparedSalesKeys = current($preparedSalesKeys);
        }

        /* @phpstan-ignore-next-line */
        collect($preparedSalesKeys)->keys()->each(function ($key) use (&$columns): void {
            if (! array_key_exists($key, $columns)) {
                $columns[$key] = $key;
            }
        });

        foreach ($previousDates as $key) {
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
        }

        return [
            $preparedSales,
            $grandTotal,
            $mainColumns,
            $columns,
            collect($yearComparison)->sortKeysDesc()->toArray(),
            collect($previousDates)->sortKeysDesc()->toArray(),
        ];
    }

    private function preparedGeneralSalesBySummaryMonth(array $filterData, bool $excludeProductsWithNoPrice): array
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $sales = $saleItemQueries->getGeneralSalesReportBySummaryWithMonthQuery(
            $filterData,
            $excludeProductsWithNoPrice
        );

        $monthNumbers = $sales->pluck('month')->unique()->filter()->sort()->toArray();

        $monthAbbreviations = [];

        foreach ($monthNumbers as $monthNumber) {
            $date = Carbon::createFromDate((int) date('Y'), $monthNumber, 1);
            $monthAbbreviations[] = $date->format('F');
        }

        $locationSaleDetails = [];
        $totals = [
            'location_name' => 'Grand Total',
            'total_collection' => [],
            'grand_total' => 0,
        ];

        foreach ($sales->sortBy('month')->groupBy(
            ['location_id', 'month'],
            true
        )->toArray() as $salesByMonthsAndLocations) {
            $sale = current(current($salesByMonthsAndLocations));
            $locationSaleDetails[$sale['location_id']] = [
                'location_name' => $sale['location_name'],
                'total_collection' => [],
                'grand_total' => 0,
            ];

            foreach (array_diff(
                array_values($monthNumbers),
                array_keys($salesByMonthsAndLocations)
            ) as $handleMissingMonth) {
                $salesByMonthsAndLocations[$handleMissingMonth] = [
                    [
                        'location_id' => $sale['location_id'],
                        'location_name' => $sale['location_name'],
                        'month' => $handleMissingMonth,
                        'sales_amount' => 0,
                    ],
                ];
            }

            ksort($salesByMonthsAndLocations);

            foreach ($salesByMonthsAndLocations as $sales) {
                $sale = current($sales);

                $totalSales = (float) $sale['sales_amount'];

                if (! array_key_exists($sale['month'], $totals['total_collection'])) {
                    $totals['total_collection'][$sale['month']] = 0;
                }

                $locationSaleDetails[$sale['location_id']]['total_collection'][] = $totalSales;
                $locationSaleDetails[$sale['location_id']]['grand_total'] += $totalSales;
                $totals['total_collection'][$sale['month']] += $totalSales;
                $totals['grand_total'] += $totalSales;
            }
        }

        $locationSaleDetails['totals'] = $totals;

        $columns = ['Location Name', ...$monthAbbreviations, 'Grand Total'];

        return [$locationSaleDetails, $columns];
    }

    public function renderPreparedGeneralSalesByCurrentDayVsPreviousDay(
        Company $company,
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): string {
        [$preparedSales, $grandTotals, $mainColumns, $columns, $yearComparisons, $previousDates] = $this->preparedGeneralSalesByCurrentDayVsPreviousDay(
            $company,
            $filterData,
            $excludeProductsWithNoPrice
        );

        return view('prints.general_sales_by_current_day_vs_previous_day', [
            'locationSales' => $preparedSales,
            'selectedDate' => $filterData['date'],
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'mainColumns' => $mainColumns,
            'filterBy' => $this->filterBy($filterData),
            'yearComparisons' => $yearComparisons,
            'previousDates' => $previousDates,
            'grandTotals' => $grandTotals,
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    public function renderPreparedGeneralSalesBySummaryMonth(
        Company $company,
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): string {
        [$preparedSales, $columns] = $this->preparedGeneralSalesBySummaryMonth(
            $filterData,
            $excludeProductsWithNoPrice
        );

        return view('prints.general_sales_by_summary_month', [
            'allLocations' => $preparedSales,
            'dateRange' => $filterData['date_range'],
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData),
            'reportType' => GeneralSalesReportTypes::getFormattedCaseName((int) $filterData['report_type']),
            'excludeByEInvoiceFilter' => $filterData['e_invoice_submitted'],
        ])->render();
    }

    private function filterBy(array $filterData): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === GeneralSalesFilterTypes::BY_PROMOTER->value && isset($filterData['promoter_ids']) && '' !== $filterData['promoter_ids']) {
            $promoters = $promoterQueries->getByIds($filterData['promoter_ids']);

            return $this->formatFilterResult(
                GeneralSalesFilterTypes::BY_PROMOTER->value,
                $promoters->pluck('employee.first_name')->implode(', ')
            );
        }

        if ($filterBy === GeneralSalesFilterTypes::BY_BRAND->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                GeneralSalesFilterTypes::BY_BRAND->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === GeneralSalesFilterTypes::BY_DEPARTMENT->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                GeneralSalesFilterTypes::BY_DEPARTMENT->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return GeneralSalesFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
