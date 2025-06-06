<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\GoodsReceivedNote\Enums\GoodsReceivedNoteFilterTypes;
use App\Domains\GoodsReceivedNote\GoodsReceivedNoteQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\Vendor\VendorQueries;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseAmount;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoodsReceivedNoteBySummaryReportService
{
    public function preparedBySummary(array $filterData, Company $company, Collection $locations): string
    {
        [$goodsReceivedNoteProducts, $columns, $dateRange] = $this->fetchRecords($filterData, $company, $locations);

        return view('prints.goods_received_note_by_summary', [
            'goodsReceivedNoteProducts' => $goodsReceivedNoteProducts,
            'dateRange' => $dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'columns' => $columns,
            'filterBy' => $this->filterBy($filterData, $company->id),
        ])->render();
    }

    public function fetchRecords(array $filterData, Company $company, Collection $locations): array
    {
        $customReportService = resolve(CustomReportService::class);
        $dateRange = $customReportService->prepareDateRange($filterData);

        $goodsReceivedNoteQueries = resolve(GoodsReceivedNoteQueries::class);
        $goodsReceivedNotes = $goodsReceivedNoteQueries->getByDateAndLocationWithProduct($filterData, $company->id);

        $goodsReceivedNotes = $this->preparedRecords($goodsReceivedNotes, $locations);

        $columns = [
            'Date',
            'UPC',
            'Article Number',
            'Name',
            ...config('app.product_variant') ? ['Attributes'] : ['Color', 'Size'],
            'Quantity',
            'Price',
        ];

        return [$goodsReceivedNotes, $columns, $dateRange];
    }

    private function preparedRecords(Collection $goodsReceivedNotes, Collection $locations): Collection
    {
        $locationsGoodsReceivedNotes = collect([]);
        $productService = resolve(ProductService::class);

        foreach ($locations as $location) {
            $locationGoodsReceivedNotes = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'goods_received_notes' => [],
            ];

            $selectedLocationGoodsReceivedNotes = $goodsReceivedNotes->where('location_id', $location->id);
            $totalQuantity = 0;

            $goodsReceivedNoteProducts = $selectedLocationGoodsReceivedNotes->pluck(
                'goodsReceivedNoteProducts'
            )->collapse();

            foreach ($goodsReceivedNoteProducts as $goodReceivedNoteProduct) {
                /** @var Product $product */
                $product = $goodReceivedNoteProduct->product;

                /** @var PurchaseAmount $purchaseAmount */
                $purchaseAmount = $goodReceivedNoteProduct->purchaseAmount;

                $totalQuantity += $goodReceivedNoteProduct->quantity;

                $articleNumber = config(
                    'app.product_variant'
                ) ? $product->masterProduct?->article_number : $product->article_number;

                $colorSizeOrAttributeData = [];
                if (config('app.product_variant')) {
                    $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
                } else {
                    $colorSizeOrAttributeData = [
                        'color' => $product->color?->name ?? 'N/A',
                        'size' => $product->size?->name ?? 'N/A',
                    ];
                }

                $locationGoodsReceivedNotes['goods_received_notes'][] = [
                    'date' => $goodReceivedNoteProduct->created_at ? $goodReceivedNoteProduct->created_at->format(
                        'd-m-Y'
                    ) : '',
                    'upc' => $product->upc,
                    'article_number' => $articleNumber,
                    'name' => $product->name,
                    ...$colorSizeOrAttributeData,
                    'quantity' => CommonFunctions::truncateDecimal((float) $goodReceivedNoteProduct->quantity),
                    'total_price' => CommonFunctions::currencyFormat((float) $purchaseAmount->landed_cost),
                    'total_price_without_formate' => $purchaseAmount->landed_cost,
                ];
            }

            $locationGoodsReceivedNotes['date'] = 'Total';
            $locationGoodsReceivedNotes['upc'] = '';
            $locationGoodsReceivedNotes['article_number'] = '';
            $locationGoodsReceivedNotes['name'] = '';
            if (config('app.product_variant')) {
                $locationGoodsReceivedNotes['attributes'] = '';
            } else {
                $locationGoodsReceivedNotes['color'] = '';
                $locationGoodsReceivedNotes['size'] = '';
            }

            $locationGoodsReceivedNotes['quantity'] = CommonFunctions::truncateDecimal($totalQuantity);
            $locationGoodsReceivedNotes['total_price'] = CommonFunctions::currencyFormat(
                collect($locationGoodsReceivedNotes['goods_received_notes'])->sum('total_price_without_formate')
            );
            $locationGoodsReceivedNotes['total_price_without_formate'] = '';

            $locationsGoodsReceivedNotes->push($locationGoodsReceivedNotes);
        }

        return $locationsGoodsReceivedNotes;
    }

    private function filterBy(array $filterData, int $companyId): string
    {
        if (! isset($filterData['filter_by'])) {
            return '';
        }

        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $vendorQueries = resolve(VendorQueries::class);

        $filterBy = (int) $filterData['filter_by'];

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_BRAND->value && isset($filterData['brand_ids']) && '' !== $filterData['brand_ids']) {
            $brands = $brandQueries->getByIds($filterData['brand_ids']);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_BRAND->value,
                $brands->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value && isset($filterData['department_ids']) && '' !== $filterData['department_ids']) {
            $departments = $departmentQueries->getByIds($filterData['department_ids']);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_DEPARTMENT->value,
                $departments->pluck('name')->implode(', ')
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_PRODUCT->value && isset($filterData['product_id']) && '' !== $filterData['product_id']) {
            $product = $productQueries->getByIdOnlyName((int) $filterData['product_id'], $companyId);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_PRODUCT->value,
                $product->compound_product_name
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value && isset($filterData['article_number']) && '' !== $filterData['article_number']) {
            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_ARTICLE_NUMBER->value,
                $filterData['article_number']
            );
        }

        if ($filterBy === GoodsReceivedNoteFilterTypes::BY_VENDOR->value && isset($filterData['vendor_ids']) && '' !== $filterData['vendor_ids']) {
            $vendors = $vendorQueries->getByIds($filterData['vendor_ids']);

            return $this->formatFilterResult(
                GoodsReceivedNoteFilterTypes::BY_VENDOR->value,
                $vendors->pluck('name')->implode(', ')
            );
        }

        return '';
    }

    private function formatFilterResult(int $filterType, ?string $value): string
    {
        return GoodsReceivedNoteFilterTypes::getFormattedCaseName($filterType) . ' (' . $value . ')';
    }
}
