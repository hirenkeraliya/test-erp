<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sale\Enums\DiscountTypeFilters;
use App\Domains\Sale\Enums\DiscountTypeReports;
use App\Domains\Sale\Exports\DiscountSummaryReportExport;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Models\Brand;
use App\Models\Department;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DiscountSummaryReportService
{
    public function print(array $filterData, int $companyId): string
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $sales = $saleItemQueries->getAllDataWithSalesAndDiscounts($filterData);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        [$saleDiscounts, $columns, $dateRange] = $this->preparedByDocument($sales, $filterData, $locations);

        return view('prints.discount_summary_report', [
            'saleDiscounts' => $saleDiscounts,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData, $companyId),
            'reportType' => $filterData['report_type'] && 0 !== $filterData['report_type'] ? DiscountTypeReports::getFormattedCaseName(
                (int) $filterData['report_type']
            ) : 'All Discount',
        ])->render();
    }

    public function export(array $filterData, int $companyId, string $filename): BinaryFileResponse
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getByIdsWithNameAndCode($company->id, $filterData['location_ids']);

        $saleItemQueries = resolve(SaleItemQueries::class);
        $sales = $saleItemQueries->getAllDataWithSalesAndDiscounts($filterData);
        [$saleDiscounts, $columns, $dateRange] = $this->preparedByDocument($sales, $filterData, $locations);

        $reportType = $filterData['report_type'] && 0 !== $filterData['report_type'] ? DiscountTypeReports::getFormattedCaseName(
            (int) $filterData['report_type']
        ) : 'All Discount';

        return Excel::download(
            new DiscountSummaryReportExport($saleDiscounts, $dateRange, $company, $columns, $this->filterBy(
                $filterData,
                $companyId
            ), $reportType),
            $filename
        );
    }

    public function preparedByDocument(Collection $saleItems, array $filterData, Collection $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationsSales = [];

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales_data' => [],
            ];

            $itemWiseDiscounts = $this->getItemWiseDiscounts(
                $saleItems->where('sale.counterUpdate.counter.location_id', $location->id)
            );

            $locationSales['sales_data'] = $this->preparedByUpc($itemWiseDiscounts);

            $locationsSales[] = $locationSales;
        }

        $columns = [
            'Name',
            'Upc',
            'Brand',
            'Department',
            config('app.product_variant') ? 'Attribute' : 'Style',
            'Tags',
            'Article Number',
            'Price',
            '5% OFF',
            '10% OFF',
            '20% OFF',
            '30% OFF',
            '40% OFF',
            'Other discounts',
        ];

        return [$locationsSales, $columns, $dateRange];
    }

    public function preparedByUpc(Collection $itemWiseDiscounts): array
    {
        $locationSales = [];
        $variantKey = config('app.product_variant') ? 'attribute' : 'style_name';
        foreach ($itemWiseDiscounts->groupBy('upc') as $collection) {
            $groupByUpcItemWiseDiscount = $collection->first();

            $locationSales[] = [
                'name' => $groupByUpcItemWiseDiscount['name'],
                'upc' => $groupByUpcItemWiseDiscount['upc'],
                'brand_name' => $groupByUpcItemWiseDiscount['brand_name'],
                'department_name' => $groupByUpcItemWiseDiscount['department_name'],
                $variantKey => $groupByUpcItemWiseDiscount[$variantKey],
                'tag_name' => $groupByUpcItemWiseDiscount['tag_name'],
                'article_number' => $groupByUpcItemWiseDiscount['article_number'],
                'price' => $groupByUpcItemWiseDiscount['price'],
                'five_per_off' => CommonFunctions::currencyFormat($collection->sum('five_per_off')),
                'ten_per_off' => CommonFunctions::currencyFormat($collection->sum('ten_per_off')),
                'twenty_per_off' => CommonFunctions::currencyFormat($collection->sum('twenty_per_off')),
                'thirty_per_off' => CommonFunctions::currencyFormat($collection->sum('thirty_per_off')),
                'forty_per_off' => CommonFunctions::currencyFormat($collection->sum('forty_per_off')),
                'other_discount' => $this->getFormateOtherDiscount($collection),
            ];
        }

        $locationSales[] = [
            'name' => '',
            'upc' => '',
            'brand_name' => '',
            'department_name' => '',
            $variantKey => '',
            'tag_name' => '',
            'article_number' => 'Total',
            'price' => '',
            'five_per_off' => CommonFunctions::currencyFormat($itemWiseDiscounts->sum('five_per_off')),
            'ten_per_off' => CommonFunctions::currencyFormat($itemWiseDiscounts->sum('ten_per_off')),
            'twenty_per_off' => CommonFunctions::currencyFormat($itemWiseDiscounts->sum('twenty_per_off')),
            'thirty_per_off' => CommonFunctions::currencyFormat($itemWiseDiscounts->sum('thirty_per_off')),
            'forty_per_off' => CommonFunctions::currencyFormat($itemWiseDiscounts->sum('forty_per_off')),
            'other_discount' => CommonFunctions::currencyFormat($itemWiseDiscounts->sum('other_discount_quantity')),
        ];

        return $locationSales;
    }

    public function getFormateOtherDiscount(Collection $groupByUpcItemWiseDiscounts): string
    {
        $otherDiscounts = $groupByUpcItemWiseDiscounts->pluck('other_discount')->where('percentage', '>', 0);
        if ($otherDiscounts->isEmpty()) {
            return '';
        }

        $otherDiscount = '';
        foreach ($otherDiscounts->groupBy('percentage') as $collection) {
            $groupByPercentageOtherDiscount = $collection->first();
            if ('' !== $otherDiscount) {
                $otherDiscount .= ', ';
            }

            $otherDiscount .= $groupByPercentageOtherDiscount['percentage'] . '% - '. CommonFunctions::currencyFormat(
                $collection->sum('quantity')
            );
        }

        return $otherDiscount;
    }

    public function getItemWiseDiscounts(Collection $saleItems): Collection
    {
        $productService = resolve(ProductService::class);

        $itemWiseDiscounts = collect([]);
        foreach ($saleItems as $saleItem) {
            /** @var Product $product */
            $product = $saleItem->product;

            /** @var Brand $brand */
            $brand = config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;

            /** @var Department $department */
            $department = config('app.product_variant') ? $product->masterProduct?->department : $product->department;

            /** @var Collection $tags */
            $tags = config('app.product_variant') ? $product->masterProduct?->tags : $product->tags;

            $tags = $tags->pluck('name')->toArray();

            $tagName = implode(',', $tags);

            /** @var Collection $saleItemDiscounts */
            $saleItemDiscounts = $saleItem->saleItemDiscounts;

            $discountsAmount = $saleItemDiscounts->sum('amount');

            $amount = $saleItem->quantity * $product->retail_price;

            $discountPercentage = CommonFunctions::numberFormat($discountsAmount * 100 / $amount);

            $variantColumn = config('app.product_variant')
                ? [
                    'attribute' => $productService->getAttributesForPrint($product),
                ]
                : [
                    'style_name' => $product->style->name ?? 'N/A',
                ];

            $itemWiseDiscounts->push([
                'name' => $product->name,
                'upc' => $product->upc,
                'brand_name' => $brand->name,
                'department_name' => $department->name ?? 'N/A',
                ...$variantColumn,
                'tag_name' => $tagName,
                'article_number' => $product->article_number ?? 'N/A',
                'price' => CommonFunctions::currencyFormat($amount),
                'five_per_off' => $this->getFivePerOffQuantity($discountPercentage, (float) $saleItem->quantity),
                'ten_per_off' => $this->getTenPerOffQuantity($discountPercentage, (float) $saleItem->quantity),
                'twenty_per_off' => $this->getTwentyPerOffQuantity($discountPercentage, (float) $saleItem->quantity),
                'thirty_per_off' => $this->getThirtyPerOffQuantity($discountPercentage, (float) $saleItem->quantity),
                'forty_per_off' => $this->getFortyPerOffQuantity($discountPercentage, (float) $saleItem->quantity),
                'other_discount' => $this->getOtherDiscount($discountPercentage, (float) $saleItem->quantity),
                'other_discount_quantity' => $this->getOtherDiscountQuantity(
                    $discountPercentage,
                    (float) $saleItem->quantity
                ),
            ]);
        }

        return $itemWiseDiscounts;
    }

    public function getFivePerOffQuantity(float $discountPercentage, float $quantity): float
    {
        if ($discountPercentage <= 5) {
            return $quantity;
        }

        return 0.00;
    }

    public function getTenPerOffQuantity(float $discountPercentage, float $quantity): float
    {
        if ($discountPercentage <= 5) {
            return 0.00;
        }

        if ($discountPercentage > 10) {
            return 0.00;
        }

        return $quantity;
    }

    public function getTwentyPerOffQuantity(float $discountPercentage, float $quantity): float
    {
        if ($discountPercentage <= 10) {
            return 0.00;
        }

        if ($discountPercentage > 20) {
            return 0.00;
        }

        return $quantity;
    }

    public function getThirtyPerOffQuantity(float $discountPercentage, float $quantity): float
    {
        if ($discountPercentage <= 20) {
            return 0.00;
        }

        if ($discountPercentage > 30) {
            return 0.00;
        }

        return $quantity;
    }

    public function getFortyPerOffQuantity(float $discountPercentage, float $quantity): float
    {
        if ($discountPercentage <= 30) {
            return 0.00;
        }

        if ($discountPercentage > 40) {
            return 0.00;
        }

        return $quantity;
    }

    public function getOtherDiscount(float $discountPercentage, float $quantity): array
    {
        if ($discountPercentage > 40) {
            return [
                'percentage' => $discountPercentage,
                'quantity' => $quantity,
            ];
        }

        return [
            'percentage' => 0,
            'quantity' => 0,
        ];
    }

    public function getOtherDiscountQuantity(float $discountPercentage, float $quantity): float
    {
        if ($discountPercentage > 40) {
            return $quantity;
        }

        return 0.00;
    }

    private function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === DiscountTypeFilters::BY_BRAND->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                DiscountTypeFilters::BY_BRAND->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === DiscountTypeFilters::BY_DEPARTMENT->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                DiscountTypeFilters::BY_DEPARTMENT->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === DiscountTypeFilters::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(DiscountTypeFilters::BY_PRODUCT->value, $product->compound_product_name);
        }

        if ($filterBy === DiscountTypeFilters::BY_MASTER_PRODUCT->value && isset($filterData['article_number']) && '' !== $filterData['article_number']) {
            return $this->formatFilterResult(
                DiscountTypeFilters::BY_MASTER_PRODUCT->value,
                $filterData['article_number']
            );
        }

        if ($filterBy === DiscountTypeFilters::BY_STYLE->value && isset($filterData['style_ids']) && '' !== $filterData['style_ids']) {
            $styles = $styleQueries->getByIds($filterData['style_ids']);

            return $this->formatFilterResult(
                DiscountTypeFilters::BY_STYLE->value,
                $styles->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === DiscountTypeFilters::BY_TAG->value && isset($filterData['tag_ids']) && '' !== $filterData['tag_ids']) {
            $products = $tagQueries->getByIds($filterData['tag_ids']);

            return $this->formatFilterResult(
                DiscountTypeFilters::BY_TAG->value,
                $products->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === DiscountTypeFilters::BY_ATTRIBUTES->value && isset($filterData['attribute_values']) && '' !== $filterData['attribute_values']) {
            $attribute = $attributeQueries->getNameById($filterData['attribute_type']);

            $formattedAttributeValues = implode(', ', $filterData['attribute_values']);

            return DiscountTypeFilters::getFormattedCaseName(
                $filterBy
            ) . ' (' . $attribute->name . ': ' . $formattedAttributeValues . ')';
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return DiscountTypeFilters::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
