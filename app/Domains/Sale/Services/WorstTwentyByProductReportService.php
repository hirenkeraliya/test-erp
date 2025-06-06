<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\WorstTwentyFilterTypes;
use App\Domains\Sale\Enums\WorstTwentyReportViewTypes;
use App\Domains\Sale\Exports\WorstTwentyByProductExport;
use App\Domains\SaleItem\SaleItemQueries;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorstTwentyByProductReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyProductRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.worst_twenty_by_products', [
            'locationsSales' => $locationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filterBy' => $this->filterBy($filterData),
            'displayAmount' => (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value,
        ])->render();
    }

    public function export(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        [$locationsSales, $company] = $this->fetchWorstTwentyProductRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === WorstTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new WorstTwentyByProductExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchWorstTwentyProductRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleItems = $saleItemQueries->getByStoreForTopProductExport($filterData);

        $locationsSales = [];
        if ($filterData['combine_stock_by_selected_location']) {
            $filteredSaleItems = $saleItems
                ->sortBy('quantity')
                ->take(20);

            $locationSales = [
                'location_name' => $locations->pluck('name')->implode(', ') . ' [' . $locations->pluck('code')->implode(
                    ', '
                ) . ']',
                'products' => $this->getProductSalesData($filteredSaleItems),
            ];

            $locationSales['total'] = $this->calculateStoreSalesTotal($locationSales['products']);

            $locationsSales[] = $locationSales;

            return [$locationsSales, $company];
        }

        foreach ($locations as $location) {
            $filteredSaleItems = $saleItems->where('sale.counterUpdate.counter.location_id', $location->id)
                ->sortBy('quantity')
                ->take(20);

            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'products' => $this->getProductSalesData($filteredSaleItems),
            ];

            $locationSales['total'] = $this->calculateStoreSalesTotal($locationSales['products']);

            $locationsSales[] = $locationSales;
        }

        return [$locationsSales, $company];
    }

    private function getProductSalesData(Collection $saleItems): array
    {
        $saleProductsData = [];

        foreach ($saleItems as $saleItem) {
            /** @var Product $product */
            $product = $saleItem->product;

            $saleProductsData[] = [
                'product_no' => $product->upc,
                'name' => $product->name,
                'qty' => $saleItem->quantity,
                'gross_sales_excl_gst' => ($saleItem->total_price_paid - $saleItem->total_tax_amount),
                'discount_amount' => $saleItem->total_discount_amount,
                'net_sales_excl_gst' => ($saleItem->total_price_paid - $saleItem->total_tax_amount - $saleItem->total_discount_amount),
                'gst_amount' => $saleItem->total_tax_amount,
                'net_sales_incl_gst' => $saleItem->total_price_paid,
            ];
        }

        return $saleProductsData;
    }

    private function calculateStoreSalesTotal(array $products): array
    {
        $collectionProducts = collect($products);

        return [
            'product_no' => 'Total',
            'name' => '',
            'qty' => $collectionProducts->sum('qty'),
            'gross_sales_excl_gst' => $collectionProducts->sum('gross_sales_excl_gst'),
            'discount_amount' => $collectionProducts->sum('discount_amount'),
            'net_sales_excl_gst' => $collectionProducts->sum('net_sales_excl_gst'),
            'gst_amount' => $collectionProducts->sum('gst_amount'),
            'net_sales_incl_gst' => $collectionProducts->sum('net_sales_incl_gst'),
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

        if ($filterBy === WorstTwentyFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                WorstTwentyFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === WorstTwentyFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                WorstTwentyFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return WorstTwentyFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
