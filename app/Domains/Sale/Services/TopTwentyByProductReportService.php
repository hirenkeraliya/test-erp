<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sale\Enums\TopTwentyFilterTypes;
use App\Domains\Sale\Enums\TopTwentyReportViewTypes;
use App\Domains\Sale\Exports\TopTwentyByProductExport;
use App\Domains\TopTwentyAggregateData\TopTwentyAggregateDataQueries;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopTwentyByProductReportService
{
    public function print(int $companyId, array $filterData): string
    {
        [$locationsSales, $company] = $this->fetchTopTwentyProductRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.top_twenty_by_products', [
            'locationsSales' => $locationsSales,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filterBy' => $this->filterBy($filterData),
            'displayAmount' => (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value,
        ])->render();
    }

    public function export(int $companyId, array $filterData, string $filename): BinaryFileResponse
    {
        [$locationsSales, $company] = $this->fetchTopTwentyProductRecords($companyId, $filterData);

        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $filterBy = $this->filterBy($filterData);

        $displayAmount = (int) $filterData['report_view_type'] === TopTwentyReportViewTypes::BY_AMOUNT->value;

        return Excel::download(
            new TopTwentyByProductExport($locationsSales, $dateRange, $company, $filterBy, $displayAmount),
            $filename
        );
    }

    private function fetchTopTwentyProductRecords(int $companyId, array $filterData): array
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $topTwentyAggregateDataQueries = resolve(TopTwentyAggregateDataQueries::class);
        $topTwentyAggregateData = $topTwentyAggregateDataQueries->getByStoreForTopProductExport($filterData);

        $locationsSales = [];
        if ($filterData['combine_stock_by_selected_location']) {
            $filteredSaleItems = $topTwentyAggregateData
                ->sortByDesc('quantity')
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
            $filteredSaleItems = $topTwentyAggregateData->where('counterUpdate.counter.location_id', $location->id)
                ->sortByDesc('quantity')
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

    private function getProductSalesData(Collection $topTwentyAggregateData): array
    {
        $saleProductsData = [];
        $productService = resolve(ProductService::class);

        foreach ($topTwentyAggregateData as $topTwentyAggregate) {
            /** @var Product $product */
            $product = $topTwentyAggregate->product;

            if ((float) $product->retail_price === 0.0) {
                continue;
            }

            $saleProductsData[] = [
                'product_no' => $product->upc,
                'name' => $product->name,
                'color' => config('app.product_variant') ? 'N/A' : $product->color?->name ?? 'N/A',
                'size' => config('app.product_variant') ? 'N/A' : $product->size?->name ?? 'N/A',
                'qty' => $topTwentyAggregate->quantity,
                'gross_sales_excl_gst' => $topTwentyAggregate->gross_sales,
                'discount_amount' => $topTwentyAggregate->discount,
                'net_sales_excl_gst' => $topTwentyAggregate->net_sales,
                'gst_amount' => $topTwentyAggregate->tax,
                'net_sales_incl_gst' => $topTwentyAggregate->total_amount,
                'attributes' => config('app.product_variant') ? $productService->getAttributesForPrint($product) : null,
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
            'color' => '',
            'size' => '',
            'qty' => $collectionProducts->sum('qty'),
            'gross_sales_excl_gst' => $collectionProducts->sum('gross_sales_excl_gst'),
            'discount_amount' => $collectionProducts->sum('discount_amount'),
            'net_sales_excl_gst' => $collectionProducts->sum('net_sales_excl_gst'),
            'gst_amount' => $collectionProducts->sum('gst_amount'),
            'net_sales_incl_gst' => $collectionProducts->sum('net_sales_incl_gst'),
            'attributes' => '',
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

        if ($filterBy === TopTwentyFilterTypes::BY_COUNTER->value && isset($filterData['counter_ids']) && '' !== $filterData['counter_ids']) {
            $counters = $counterQueries->getByIds($filterData['counter_ids']);

            return $this->formatFilterResult(
                TopTwentyFilterTypes::BY_COUNTER->value,
                $counters->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === TopTwentyFilterTypes::BY_CASHIER->value && isset($filterData['cashier_ids']) && '' !== $filterData['cashier_ids']) {
            $cashiers = $cashierQueries->getByIds($filterData['cashier_ids']);

            return $this->formatFilterResult(
                TopTwentyFilterTypes::BY_CASHIER->value,
                $cashiers->pluck('employee.first_name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return TopTwentyFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
