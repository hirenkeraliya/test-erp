<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Services\ExportService;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Models\Brand;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PromoterCommisionPrintService
{
    public function printPromoterCommissionDetails(array $filterData, int $promoterCommissionId): string
    {
        $productService = resolve(ProductService::class);
        $productVariant = config('app.product_variant');

        $promoterCommissionDetails = [];
        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);

        $commissionDetails = $promoterCommissionUpdateQueries->getPromoterCommissionDetailsForExport(
            $filterData,
            $promoterCommissionId
        );

        $promoterCommissionDetails['promoterCommissionDetails'] = $commissionDetails->map(
            function ($commissionDetail) use ($productService): array {
                $saleOrSaleReturn = null;
                if ($commissionDetail->affected_by_type === ModelMapping::SALE_RETURN_ITEM->name) {
                    $saleOrSaleReturn = $commissionDetail->affected_by->saleReturn;
                }

                if ($commissionDetail->affected_by_type === ModelMapping::SALE_ITEM->name) {
                    $saleOrSaleReturn = $commissionDetail->affected_by->sale;
                }

                /** @var Department $department */
                $department = $commissionDetail->department;
                /** @var CounterUpdate $counterUpdate */
                $counterUpdate = $saleOrSaleReturn->counterUpdate;
                /** @var Counter $counter */
                $counter = $counterUpdate->counter;
                /** @var Location $location */
                $location = $counter->location;
                $product = $commissionDetail->affected_by->product;

                /** @var Brand|null $brand */
                $brand = config('app.product_variant') ? $product?->masterProduct?->brand : $product->brand;

                $colorSizeOrAttributeData = [];
                if (config('app.product_variant')) {
                    $colorSizeOrAttributeData['attributes'] = $productService->getAttributesForPrint($product);
                } else {
                    $colorSizeOrAttributeData = [
                        'color' => $product->color?->name ?? 'N/A',
                        'size' => $product->size?->name ?? 'N/A',
                    ];
                }

                return [
                    'offline_id' => $commissionDetail->getOfflineId($commissionDetail->affected_by_type),
                    'product' => $product->name,
                    'brand' => $brand->name ?? 'N/A',
                    ...$colorSizeOrAttributeData,
                    'department' => $department->name ?? 'N/A',
                    'location_name' => $location->name,
                    'units' => $commissionDetail->affected_by->quantity,
                    'commission_percentage' => $commissionDetail->commission_percentage,
                    'amount' => CommonFunctions::currencyFormat((float) $commissionDetail->amount),
                    'sale_amount' => (float) $commissionDetail->amount,
                    'commission_amount' => CommonFunctions::currencyFormat(
                        (float) $commissionDetail->commission_amount,
                        4
                    ),
                ];
            }
        );

        $promoterCommissionDetails['sale_amount'] = $promoterCommissionDetails['promoterCommissionDetails']->sum(
            'sale_amount'
        );
        $promoterCommissionDetails['commission_amount'] = CommonFunctions::currencyFormat(
            (float) $promoterCommissionDetails['promoterCommissionDetails']->sum('commission_amount')
        );

        return view('prints.promoter_commission_details', [
            'promoterCommissionDetails' => $promoterCommissionDetails['promoterCommissionDetails']->toArray(),
            'totalSaleAmount' => $promoterCommissionDetails['sale_amount'],
            'totalCommissionAmount' => $promoterCommissionDetails['commission_amount'],
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'productVariant' => $productVariant,
        ])->render();
    }

    public function printPromoterCommission(array $filterData, int $companyId, Collection $filterColumns): string
    {
        $exportService = resolve(ExportService::class);
        $companyQueries = resolve(CompanyQueries::class);
        $promoterCommissionQueries = resolve(PromoterCommissionQueries::class);

        $promoterCommissionDetails = $promoterCommissionQueries->getPaginatedCommissionByPromotersForMonthForExport(
            $filterData,
            $companyId
        );

        $filterData['date'] = $this->prepareMonthRange($filterData);
        $printPdfHeaderFilter = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilter->buildFilterData($filterData);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);

        $details = [];
        $totalSalesAmount = 0;
        $totalCommissionAmount = 0;

        foreach ($promoterCommissionDetails as $promoterCommissionDetail) {
            /** @var Promoter $promoter */
            $promoter = $promoterCommissionDetail->promoter;

            /** @var Employee $employee */
            $employee = $promoter->employee;

            /** @var Collection $locations description */
            $locations = $promoter->locations;

            $date = Carbon::createFromFormat('Y-m-d', $promoterCommissionDetail['commission_date']);

            $commissionDate = $date ? $date->format('F Y') : '';

            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            $promoterCommissionUpdates = $promoterCommissionDetail->promoterCommissionUpdates;

            $totalSalesAmount += $promoterCommissionUpdates->sum('amount');
            $totalCommissionAmount += $promoterCommissionUpdates->sum('commission_amount');

            $detail = [
                'id' => $promoterCommissionDetail->id,
                'staff_id' => $employee->staff_id,
                'designation' => $employee->designation?->name,
                'promoter' => $employee->getFullName(),
                'locations' => $locationNames,
                'commission_date' => $commissionDate,
                'monthly_sales_target' => (CommissionTypes::BY_PROMOTER === $company->commission_type_id) ? $promoterCommissionDetail->monthly_sales_target : 0,
                'total_sales_amount' => CommonFunctions::currencyFormat(
                    (float) $promoterCommissionUpdates->sum('amount')
                ),
                'commission_amount' => CommonFunctions::currencyFormat(
                    (float) $promoterCommissionUpdates->sum('commission_amount')
                ),
            ];

            if (CommissionTypes::BY_PROMOTER !== $company->commission_type_id) {
                $detail['total_sales_amount_without_format'] = (float) $promoterCommissionUpdates->sum('amount');
                $detail['commission_amount_without_format'] = (float) $promoterCommissionUpdates->sum(
                    'commission_amount'
                );
            }

            $details[] = $exportService->exportData($detail, $filterColumns);
        }

        $columns = [
            'total_sales_amount' => $totalSalesAmount,
            'commission_amount' => $totalCommissionAmount,
        ];

        $preparedData = [];

        $filteredColumns = $filterColumns->filter(fn ($column): bool => isset($columns[$column]));

        if ($filteredColumns->isNotEmpty() && [] !== $details) {
            $summaryData = $this->createDynamicSummaryArrayForExportAndPdf(
                $details[0],
                $filteredColumns->contains('total_sales_amount') ? $columns['total_sales_amount'] : null,
                $filteredColumns->contains('commission_amount') ? $columns['commission_amount'] : null
            );

            $preparedData[] = count($details[0]) === $filteredColumns->count()
                ? $summaryData
                : array_merge(['Total'], $summaryData);
        }

        return view('prints.promoter_commission_report', [
            'promoterCommissions' => array_merge($details, $preparedData),
            'company' => $company,
            'filterHeaderData' => $filterHeaderData,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'columns' => $exportService->getHeadings($filterColumns),
            'columnsKeys' => $filterColumns->reject(fn ($item): bool => 'info' === $item)->values(),
        ])->render();
    }

    public function createDynamicSummaryArrayForExportAndPdf(
        array $firstElement,
        ?float $totalSalesAmount,
        ?float $commissionAmount
    ): array {
        $numKeys = count($firstElement);
        $blankKeys = (null !== $totalSalesAmount && null !== $commissionAmount) ? max($numKeys - 3, 0) : max(
            $numKeys - 2,
            0
        );

        $summaryArray = array_fill(0, $blankKeys, '');

        if (null !== $totalSalesAmount) {
            $summaryArray['total_sales_amount'] = CommonFunctions::currencyFormat($totalSalesAmount);
        }

        if (null !== $commissionAmount) {
            $summaryArray['commission_amount'] = CommonFunctions::currencyFormat($commissionAmount);
        }

        return $summaryArray;
    }

    private function prepareMonthRange(array $filterData): string
    {
        $date = $filterData['month_range'][1] . '-' . $filterData['month_range'][0] . '-' . now()->format('d');

        /** @var Carbon $format */
        $format = Carbon::createFromFormat('Y-m-d', $date);

        return $format->format('F Y');
    }
}
