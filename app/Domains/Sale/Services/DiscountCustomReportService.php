<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Sale\Enums\DiscountTypeFilters;
use App\Domains\Sale\Enums\DiscountTypeReports;
use App\Domains\Sale\Exports\DiscountReportExport;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItemComplimentary;
use App\Models\SaleItemPriceOverride;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DiscountCustomReportService
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

        return view('prints.discount_report', [
            'saleDiscounts' => $saleDiscounts,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData, $companyId),
            'reportType' => DiscountTypeReports::getFormattedCaseName((int) $filterData['report_type']),
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

        $reportType = $filterData['report_type'] ? DiscountTypeReports::getFormattedCaseName(
            (int) $filterData['report_type']
        ) : '';

        $filterBy = $this->filterBy($filterData, $companyId);

        return Excel::download(
            new DiscountReportExport($saleDiscounts, $dateRange, $company, $columns, $filterBy, $reportType),
            $filename
        );
    }

    public function preparedByDocument(Collection $saleItems, array $filterData, Collection $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $productService = resolve(ProductService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $locationsSales = [];
        $isEmployeeRequired = false;

        foreach ($locations as $location) {
            $locationSales = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'sales_data' => [],
            ];

            $totalQuantity = 0;
            $totalPrice = 0;
            $totalItemDiscount = 0;
            $totalNetSales = 0;
            $totalVariation = 0;

            foreach ($saleItems->where('sale.counterUpdate.counter.location_id', $location->id)->sortBy(
                'sale.happened_at'
            ) as $saleItem) {
                /** @var Sale $sale */
                $sale = $saleItem->sale;

                /** @var CounterUpdate $counterUpdate */
                $counterUpdate = $sale->counterUpdate;

                /** @var Counter $counter */
                $counter = $counterUpdate->counter;

                /** @var Cashier $cashier */
                $cashier = $counterUpdate->cashier;

                /** @var Employee $employee */
                $employee = $cashier->employee;

                /** @var Location $location */
                $location = $counter->location;

                /** @var Product $product */
                $product = $saleItem->product;

                /** @var Brand $brand */
                $brand = config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;

                /** @var Department $department */
                $department = config(
                    'app.product_variant'
                ) ? $product->masterProduct?->department : $product->department;

                /** @var Collection $tags */
                $tags = config('app.product_variant') ? $product->masterProduct?->tags : $product->tags;

                $employeeName = null;

                /** @var ?SaleItemComplimentary $saleItemComplimentary */
                $saleItemComplimentary = $saleItem->saleItemComplimentary;

                if ($saleItemComplimentary instanceof SaleItemComplimentary) {
                    $saleItemComplimentaryAuthorizer = $saleItemComplimentary->authorizer;

                    /** @var Employee $saleItemComplimentaryEmployee */
                    $saleItemComplimentaryEmployee = $saleItemComplimentaryAuthorizer->employee;

                    $employeeName = Str::of($saleItemComplimentary->authorizer_type)->replace(
                        '_',
                        ' '
                    )->title()->value() . ' : ' . $saleItemComplimentaryEmployee->getFullName();
                }

                /** @var ?SaleItemPriceOverride $saleItemPriceOverride */
                $saleItemPriceOverride = $saleItem->saleItemPriceOverride;

                if ($saleItemPriceOverride instanceof SaleItemPriceOverride) {
                    $saleItemPriceOverrideNegotiator = $saleItemPriceOverride->negotiator;

                    /** @var Employee $saleItemPriceOverrideEmployee */
                    $saleItemPriceOverrideEmployee = $saleItemPriceOverrideNegotiator->employee;

                    $employeeName = Str::of($saleItemPriceOverride->negotiator_type)->replace(
                        '_',
                        ' '
                    )->title()->value() . ' : ' . $saleItemPriceOverrideEmployee->getFullName();
                }

                $isEmployeeRequired = $saleItem->saleItemDiscounts->first()->discountable_type === ModelMapping::COMPLIMENTARY_ITEM_REASON->name || $saleItem->saleItemDiscounts->first()->discountable_type === ModelMapping::SALE_ITEM_PRICE_OVERRIDE->name;

                $tags = $tags->pluck('name')->toArray();

                $tagName = implode(',', $tags);

                /** @var Collection $saleItemDiscounts */
                $saleItemDiscounts = $saleItem->saleItemDiscounts;

                $discountsAmount = $saleItemDiscounts->sum('amount');

                $amount = $saleItem->quantity * $product->retail_price;

                $totalQuantity += $saleItem->quantity;
                $totalPrice += $amount;
                $totalItemDiscount += $discountsAmount;
                $totalNetSales += $amount - $discountsAmount;
                $totalVariation += $discountsAmount;

                /** @var Carbon $happenedAtFormat */
                $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $sale->happened_at);
                $happenedAt = $happenedAtFormat->format('d-m-Y');

                $variantColumn = config('app.product_variant')
                    ? [
                        'attribute' => $productService->getAttributesForPrint($product),
                    ]
                    : [
                        'style_name' => $product->style->name ?? 'N/A',
                    ];

                $locationSales['sales_data'][] = [
                    'location_code' => $location->code,
                    'counter_code' => $counter->name,
                    'cashier_code' => $employee->staff_id,
                    'employee_name' => $saleItemComplimentary instanceof SaleItemComplimentary ? $employeeName : ($saleItemPriceOverride instanceof SaleItemPriceOverride ? $employeeName : null),
                    'date' => $happenedAt,
                    'upc' => $product->upc,
                    'name' => $product->name,
                    'brand_name' => $brand->name,
                    'department_name' => $department->name ?? 'N/A',
                    ...$variantColumn,
                    'tag_name' => $tagName,
                    'article_number' => $product->article_number ?? 'N/A',
                    'quantity' => $saleItem->quantity,
                    'price' => CommonFunctions::currencyFormat($amount),
                    'item_discount' => CommonFunctions::currencyFormat($discountsAmount),
                    'percentage' => $amount ? CommonFunctions::currencyFormat($discountsAmount * 100 / $amount) : 0,
                    'net_sales' => CommonFunctions::currencyFormat($amount - $discountsAmount),
                    'variation' => '-' . CommonFunctions::currencyFormat($discountsAmount),
                ];
            }

            $locationSales['location_code'] = '';
            $locationSales['counter_code'] = '';
            $locationSales['cashier_code'] = '';
            $locationSales['employee_name'] = '';
            $locationSales['date'] = '';
            $locationSales['upc'] = '';
            $locationSales['name'] = '';
            $locationSales['brand_name'] = '';
            $locationSales['department_name'] = '';
            $locationSales[config('app.product_variant') ? 'attribute' : 'style_name'] = '';
            $locationSales['tag_name'] = '';
            $locationSales['article_number'] = 'Total';
            $locationSales['quantity'] = CommonFunctions::truncateDecimal($totalQuantity);
            $locationSales['price'] = CommonFunctions::truncateDecimal($totalPrice);
            $locationSales['item_discount'] = CommonFunctions::truncateDecimal($totalItemDiscount);
            $locationSales['percentage'] = '';
            $locationSales['net_sales'] = CommonFunctions::truncateDecimal($totalNetSales);
            $locationSales['variation'] = CommonFunctions::truncateDecimal($totalVariation);

            $locationsSales[] = $locationSales;
        }

        $columnsWithoutEmployeeName = [
            'Location Code',
            'Counter Code',
            'Cashier Code',
            'Date',
            'Upc',
            'Name',
            'Brand',
            'Department',
            config('app.product_variant') ? 'Attribute' : 'Style',
            'Tags',
            'Article Number',
            'Quantity',
            'Price',
            'Item Discount',
            'Percentage',
            'Net Sales',
            'Variation',
        ];

        $columnsWithEmployeeName = [
            'Location Code',
            'Counter Code',
            'Cashier Code',
            'Employee Name',
            'Date',
            'Upc',
            'Name',
            'Brand',
            'Department',
            config('app.product_variant') ? 'Attribute' : 'Style',
            'Tags',
            'Article Number',
            'Quantity',
            'Price',
            'Item Discount',
            'Percentage',
            'Net Sales',
            'Variation',
        ];

        $columns = $isEmployeeRequired ? $columnsWithEmployeeName : $columnsWithoutEmployeeName;

        return [$locationsSales, $columns, $dateRange];
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
