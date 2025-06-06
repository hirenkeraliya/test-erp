<?php

declare(strict_types=1);

namespace App\Domains\Consignment\Services;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConsignmentReportService
{
    public function print(array $filterData, int $companyId, Collection $filteredColumns): string
    {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getConsignmentReportForExport($filterData, $companyId);

        $productsData = $this->preparedData($products, $filteredColumns);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        return view('prints.consignment_report', [
            'products' => $productsData,
            'company' => $company,
            'columns' => $filteredColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function preparedData(Collection $products, Collection $filteredColumns): Collection
    {
        $productService = resolve(ProductService::class);
        $data = collect([]);
        foreach ($products as $product) {
            $saleItems = $product->saleItems;
            $unitSold = $saleItems->sum('quantity');
            $total = $unitSold * $product->retail_price;
            $vendor = config('app.product_variant') ? $product->masterProduct?->vendor : $product->vendor;
            $commission = $vendor ? ($total * $vendor->commission_percentage) / 100 : 0;
            $categories = config('app.product_variant') ? $product->masterProduct?->categories->pluck(
                'name'
            )->toArray() ?? [] : $product->categories->pluck('name')->toArray();

            $colorSizeOrAttributeData = [];
            if (config('app.product_variant')) {
                $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
            } else {
                $colorSizeOrAttributeData = [
                    'color' => $product->color?->name ?? 'N/A',
                    'size' => $product->size?->name ?? 'N/A',
                ];
            }

            $data->push([
                'product' => $product->name,
                'upc' => $product->upc,
                'article_number' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
                'vendor' => $vendor ? $vendor->name : 'N/A',
                'categories' => implode(', ', $categories),
                'brand' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->brand?->name : $product->brand?->name,
                ...$colorSizeOrAttributeData,
                'unit_sold' => $unitSold,
                'price' => CommonFunctions::numberFormatString((float) $product->retail_price),
                'total' => CommonFunctions::numberFormatString((float) $total),
                'commission' => CommonFunctions::numberFormatString((float) $commission),
            ]);
        }

        $exportService = resolve(ExportService::class);

        return $exportService->exportDataMapping($data, $filteredColumns);
    }
}
