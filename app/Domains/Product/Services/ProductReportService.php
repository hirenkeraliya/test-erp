<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\Domains\Common\Services\ExportService;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductReportService
{
    public function print(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productQueries = resolve(ProductQueries::class);

        $prepareFilterData = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $prepareFilterData->buildFilterData($filterData);

        $products = $productQueries->getProductsReportForExport($filterData, $companyId);

        $productsData = $this->preparedData($products, $filterColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $grandTotals = [
            'total_quantity_sold' => $products->sum('sum_sale_quantity'),
            'total_amount_sold' => $products->sum('sum_sale_amount'),
            'total_quantity_returned' => $products->sum('sum_sale_return_quantity'),
            'total_returned_amount' => $products->sum('sum_sale_return_amount'),
        ];

        return view('prints.product_report', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
            'grand_totals' => $grandTotals,
        ])->render();
    }

    public function preparedData(Collection $products, Collection $filterColumns): Collection
    {
        $productService = resolve(ProductService::class);

        return $products->map(function ($product) use ($filterColumns, $productService): array {
            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getJsonAttributeToString(
                    $product->product_variants
                );
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color_name ?? 'N/A',
                    'size' => $product->size_name ?? 'N/A',
                ];
            }

            $productData = [
                'id' => $product->id,
                'product' => $product->name,
                'upc' => $product->upc,
                'article_number' => $product->article_number ?: 'N/A',
                'categories' => $product->category_names ?: 'N/A',
                'brand' => $product->brand_name,
                'season' => $product->season_name ?? 'N/A',
                'department' => $product->department_name ?? 'N/A',
                ...$colorSizeOrAttributeData,
                'sub_department' => $product->sub_department_id ? SubDepartments::getFormattedCaseName(
                    $product->sub_department_id
                ) : 'N/A',
                'unit_of_measure' => $product->unit_of_measure_name ?? 'N/A',
                'units_sold' => $product->sum_sale_quantity ?? 0,
                'total_sales' => $product->sum_sale_amount ?? 0,
                'units_returned' => $product->sum_sale_return_quantity ?? 0,
                'total_sale_returns' => $product->sum_sale_return_amount ?? 0,
                'tags' => $product->tag_names,
                'location' => $product->location_name,
                'verification_count' => $product->verification_count ?? 0,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productData, $filterColumns);
        });
    }

    public function onlineProductPrint(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productQueries = resolve(ProductQueries::class);

        $prepareFilterData = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $prepareFilterData->buildFilterData($filterData);

        $products = $productQueries->getOnlineProductsReportForExport($filterData, $companyId);

        $productsData = $this->preparedDataForOnlineProduct($products, $filterColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $grandTotals = [
            'total_quantity_sold' => $products->sum('sum_order_quantity'),
            'total_amount_sold' => $products->sum('sum_order_amount'),
            'total_quantity_returned' => $products->sum('sum_order_return_quantity'),
            'total_returned_amount' => $products->sum('sum_order_return_amount'),
        ];

        return view('prints.online_product_report', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
            'grand_totals' => $grandTotals,
        ])->render();
    }

    public function preparedDataForOnlineProduct(Collection $products, Collection $filterColumns): Collection
    {
        $productService = resolve(ProductService::class);

        return $products->map(function ($product) use ($filterColumns, $productService): array {
            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getJsonAttributeToString(
                    $product->product_variants
                );
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color_name ?? 'N/A',
                    'size' => $product->size_name ?? 'N/A',
                ];
            }

            $productData = [
                'id' => $product->id,
                'product' => $product->name,
                'upc' => $product->upc,
                'article_number' => $product->article_number ?: 'N/A',
                'categories' => $product->category_names ?: 'N/A',
                'brand' => $product->brand_name,
                'season' => $product->season_name ?? 'N/A',
                'department' => $product->department_name ?? 'N/A',
                ...$colorSizeOrAttributeData,
                'sub_department' => $product->sub_department_id ? SubDepartments::getFormattedCaseName(
                    $product->sub_department_id
                ) : 'N/A',
                'unit_of_measure' => $product->unit_of_measure_name ?? 'N/A',
                'units_sold' => $product->sum_order_quantity ?? 0,
                'total_orders' => $product->sum_order_amount ?? 0,
                'units_returned' => $product->sum_order_return_quantity ?? 0,
                'total_order_returns' => $product->sum_order_return_amount ?? 0,
                'tags' => $product->tag_names,
                'location' => $product->location_name,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productData, $filterColumns);
        });
    }
}
