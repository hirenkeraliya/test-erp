<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProfitAndLossReportService
{
    public function print(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getProfitsAndLossesReportForExport($filterData, $companyId);

        $productsData = $this->preparedData($products, $filterColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $grandTotals = [
            'total_quantity_sold' => $products->sum('total_quantity_sold'),
            'total_amount_sold' => $products->sum('total_amount_sold'),
            'total_quantity_returned' => $products->sum('total_quantity_returned'),
            'total_returned_amount' => $products->sum('total_returned_amount'),
            'total_purchase_cost' => $products->sum('total_purchase_cost'),
            'total_profits_or_losses' => CommonFunctions::numberFormat(
                $products->sum('total_amount_sold') - ($products->sum('total_purchase_cost') + $products->sum(
                    'total_returned_amount'
                ))
            ),
        ];

        resolve(ProductQueries::class);

        $printPdfHeaderFilter = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilter->buildFilterData($filterData);

        return view('prints.profit_and_loss_report', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'grand_totals' => $grandTotals,
            'filter_header_data' => $filterHeaderData,
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
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
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
                'units_sold' => $product->total_quantity_sold ?? 0,
                'total_sales' => $product->total_amount_sold ?? 0,
                'units_returned' => $product->total_quantity_returned ?? 0,
                'total_sale_returns' => $product->total_returned_amount ?? 0,
                'total_purchase_cost' => $product->total_purchase_cost,
                'total_profit_or_loss' => CommonFunctions::numberFormat(
                    $product->total_amount_sold - ($product->total_purchase_cost + $product->total_returned_amount)
                ),
                'tags' => $product->tag_names,
                'location' => $product->location,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productData, $filterColumns);
        });
    }
}
