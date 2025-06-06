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
use App\Domains\Vendor\VendorQueries;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoodsReceivedNoteByDocumentReportService
{
    public function preparedByDocument(array $filterData, Company $company, Collection $location): string
    {
        [$goodsReceivedNotes, $columns, $dateRange] = $this->fetchRecords($filterData, $company, $location);

        return view('prints.goods_received_note_by_document', [
            'goodsReceivedNotes' => $goodsReceivedNotes,
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
        $goodsReceivedNotes = $goodsReceivedNoteQueries->getByDateAndLocationsWithGoodsReceivedNoteProduct(
            $filterData,
            $company->id
        );

        $goodsReceivedNotes = $this->preparedRecords($goodsReceivedNotes, $locations);

        $columns = ['Date', 'Grn Ref', 'Do Ref', 'Po Ref', 'Quantity', 'Notes', 'Created By'];

        return [$goodsReceivedNotes, $columns, $dateRange];
    }

    private function preparedRecords(Collection $goodsReceivedNotes, Collection $locations): Collection
    {
        $locationsGoodsReceivedNotes = collect([]);

        foreach ($locations as $location) {
            $locationGoodsReceivedNotes = [
                'location_name' => $location->name . ' [' . $location->code . ']',
                'goods_received_notes' => [],
            ];

            $selectedLocationGoodsReceivedNotes = $goodsReceivedNotes->where('location_id', $location->id);
            $totalQuantity = 0;

            foreach ($selectedLocationGoodsReceivedNotes as $selectedLocationGoodReceivedNote) {
                $goodsReceivedNoteProducts = $selectedLocationGoodReceivedNote->goodsReceivedNoteProducts;
                $createdBy = $selectedLocationGoodReceivedNote->createdBy;
                $employee = $createdBy->employee;

                $totalQuantity += $goodsReceivedNoteProducts->sum('quantity');
                $locationGoodsReceivedNotes['goods_received_notes'][] = [
                    'date' => $selectedLocationGoodReceivedNote->created_at->format('d-m-Y'),
                    'grn_ref' => $selectedLocationGoodReceivedNote->grn_reference,
                    'do_ref' => $selectedLocationGoodReceivedNote->purchase_order_reference,
                    'po_ref' => $selectedLocationGoodReceivedNote->delivery_order_reference,
                    'total_quantity' => CommonFunctions::truncateDecimal($goodsReceivedNoteProducts->sum('quantity')),
                    'notes' => $selectedLocationGoodReceivedNote->notes,
                    'created_by' => $employee->getFullName() . ' (' . $employee->staff_id . ')',
                ];
            }

            $locationGoodsReceivedNotes['date'] = 'Total';
            $locationGoodsReceivedNotes['grn_ref'] = '';
            $locationGoodsReceivedNotes['do_ref'] = '';
            $locationGoodsReceivedNotes['po_ref'] = '';
            $locationGoodsReceivedNotes['total_quantity'] = CommonFunctions::truncateDecimal($totalQuantity);
            $locationGoodsReceivedNotes['notes'] = '';
            $locationGoodsReceivedNotes['created_by'] = '';

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
