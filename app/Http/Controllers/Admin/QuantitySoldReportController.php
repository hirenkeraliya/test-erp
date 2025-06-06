<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\AdminQuantitySoldReportResource;
use App\Domains\QuantitySold\Enums\ReportTypes;
use App\Domains\QuantitySold\Exports\QuantitySoldReportExport;
use App\Domains\Region\RegionQueries;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuantitySoldReportController extends Controller
{
    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $regionQueries = resolve(RegionQueries::class);
        $regions = $regionQueries->getRegionByCompanyId($companyId);

        $regions->prepend([
            'id' => 0,
            'name' => 'ALL REGIONS',
            'code' => '',
        ]);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'));
        }

        return Inertia::render('reports/quantity_sold_reports/Index', [
            'locations' => $locations,
            'regions' => $regions,
            'reportTypes' => ReportTypes::getList(),
            'staticReportTypes' => ReportTypes::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('quantity_sold'),
            'helpCenterMessages' => 'The sell-through report display comprehensive analysis and insights by advanced filters such as categories, sizes, stores, article numbers within date range only. Additionally, display pie and bar charts for visual representation.',
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function fetchQuantitySold(Request $request): array
    {
        $lengthAwarePaginator = null;
        $consolidateData = null;
        $companyId = session('admin_company_id');

        $filterData = [
            'per_page' => $request->get('per_page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'separate_column_sorting' => $request->get('separate_column_sorting'),
            'compare_sort_by' => $request->get('compare_sort_by'),
            'compare_sort_direction' => $request->get('compare_sort_direction'),
            'date_range' => $request->get('date_range'),
            'location_id' => (int) $request->get('location_id'),
            'compare_location_id' => (int) $request->get('compare_location_id'),
            'region_id' => (int) $request->get('region_id'),
            'compare_region_id' => (int) $request->get('compare_region_id'),
            'article_numbers' => $request->get('article_numbers'),
            'color_ids' => $request->get('color_ids'),
            'style_ids' => $request->get('style_ids'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'size_ids' => $request->get('size_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'department_ids' => $request->get('department_ids'),
            'report_type' => (int) $request->get('report_type'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = null;
        if (0 !== $filterData['location_id']) {
            $location = $locationQueries->getByIdWithNameAndCode($companyId, $filterData['location_id']);
        }

        $compareLocation = null;
        if (0 !== $filterData['compare_location_id']) {
            $compareLocation = $locationQueries->getByIdWithNameAndCode(
                $companyId,
                $filterData['compare_location_id'],
            );
        }

        $regionQueries = resolve(RegionQueries::class);
        $region = null;
        if (0 !== $filterData['region_id']) {
            $region = $regionQueries->getById($filterData['region_id'], $companyId);
        }

        $compareRegion = null;
        if (0 !== $filterData['compare_region_id']) {
            $compareRegion = $regionQueries->getById($filterData['compare_region_id'], $companyId);
        }

        if ($filterData['separate_column_sorting']) {
            [$lengthAwarePaginator, $compareLengthAwarePaginator, $consolidateData] = $this->getReportDataAndConsolidateWithDifferentSort(
                $filterData,
                $companyId
            );

            return [
                'total_records' => $lengthAwarePaginator->total(),
                'location_name' => $location?->name,
                'compare_location_name' => $compareLocation?->name,
                'region_name' => $region?->name ?? 'ALL REGIONS',
                'compare_region_name' => $compareRegion?->name ?? 'ALL REGIONS',
                'products' => AdminQuantitySoldReportResource::collection($lengthAwarePaginator->getCollection()),
                'compared_products' => AdminQuantitySoldReportResource::collection(
                    $compareLengthAwarePaginator->getCollection()
                ),
                'total_sum_and_counts' => [
                    'quantity' => $consolidateData->sum('total_quantity_sold') - $consolidateData->sum(
                        'total_quantity_returned'
                    ),
                    'amount' => $consolidateData->sum('total_amount_sold') - $consolidateData->sum(
                        'total_returned_amount'
                    ),
                    'compare_quantity' => $consolidateData->sum('compare_total_quantity_sold') - $consolidateData->sum(
                        'compare_total_quantity_returned'
                    ),
                    'compare_amount' => $consolidateData->sum('compare_total_amount_sold') - $consolidateData->sum(
                        'compare_total_returned_amount'
                    ),
                ],
            ];
        }

        [$lengthAwarePaginator, $consolidateData] = $this->getReportDataAndConsolidate($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'location_name' => $location?->name,
            'compare_location_name' => $compareLocation?->name,
            'region_name' => $region?->name ?? 'ALL REGIONS',
            'compare_region_name' => $compareRegion?->name ?? 'ALL REGIONS',
            'products' => AdminQuantitySoldReportResource::collection($lengthAwarePaginator->getCollection()),
            'total_sum_and_counts' => [
                'quantity' => $consolidateData->sum('total_quantity_sold') - $consolidateData->sum(
                    'total_quantity_returned'
                ),
                'amount' => $consolidateData->sum('total_amount_sold') - $consolidateData->sum('total_returned_amount'),
                'compare_quantity' => $consolidateData->sum('compare_total_quantity_sold') - $consolidateData->sum(
                    'compare_total_quantity_returned'
                ),
                'compare_amount' => $consolidateData->sum('compare_total_amount_sold') - $consolidateData->sum(
                    'compare_total_returned_amount'
                ),
            ],
        ];
    }

    public function printQuantitySold(Request $request): string
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'per_page' => $request->get('per_page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'separate_column_sorting' => $request->get('separate_column_sorting'),
            'compare_sort_by' => $request->get('compare_sort_by'),
            'compare_sort_direction' => $request->get('compare_sort_direction'),
            'date_range' => $request->get('date_range'),
            'location_id' => (int) $request->get('location_id'),
            'compare_location_id' => (int) $request->get('compare_location_id'),
            'region_id' => (int) $request->get('region_id'),
            'compare_region_id' => (int) $request->get('compare_region_id'),
            'article_numbers' => $request->get('article_numbers'),
            'color_ids' => $request->get('color_ids'),
            'style_ids' => $request->get('style_ids'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'size_ids' => $request->get('size_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'department_ids' => $request->get('department_ids'),
            'report_type' => (int) $request->get('report_type'),
            'location_type' => $request->get('location_type'),
            'compare_location_type' => $request->get('compare_location_type'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $prepareFilterData = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $prepareFilterData->buildFilterData($filterData);

        $locationQueries = resolve(LocationQueries::class);
        $location = null;
        if (0 !== $filterData['location_id']) {
            $location = $locationQueries->getByIdWithNameAndCode($companyId, $filterData['location_id']);
        }

        $compareLocation = null;
        if (0 !== $filterData['compare_location_id']) {
            $compareLocation = $locationQueries->getByIdWithNameAndCode(
                $companyId,
                $filterData['compare_location_id'],
            );
        }

        $regionQueries = resolve(RegionQueries::class);
        $region = null;
        if (0 !== $filterData['region_id']) {
            $region = $regionQueries->getById($filterData['region_id'], $companyId);
        }

        $compareRegion = null;
        if (0 !== $filterData['compare_region_id']) {
            $compareRegion = $regionQueries->getById($filterData['compare_region_id'], $companyId);
        }

        $records = $this->getReportDataForPrint($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        return view('prints.quantity_sold_report', [
            'locationName' => $location?->name,
            'compareLocationName' => $compareLocation?->name,
            'regionName' => $region?->name ?? 'ALL REGIONS',
            'compareRegionName' => $compareRegion?->name ?? 'ALL REGIONS',
            'records' => $filterData['separate_column_sorting'] ? $records['records'] : $records,
            'comparedRecords' => $filterData['separate_column_sorting'] ? $records['compareRecords'] : $records,
            'dateRange' => $filterData['date_range'],
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function exportQuantitySold(Request $request, string $filename): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'per_page' => $request->get('per_page'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'separate_column_sorting' => $request->get('separate_column_sorting'),
            'compare_sort_by' => $request->get('compare_sort_by'),
            'compare_sort_direction' => $request->get('compare_sort_direction'),
            'date_range' => $request->get('date_range'),
            'location_id' => (int) $request->get('location_id'),
            'compare_location_id' => (int) $request->get('compare_location_id'),
            'region_id' => (int) $request->get('region_id'),
            'compare_region_id' => (int) $request->get('compare_region_id'),
            'article_numbers' => $request->get('article_numbers'),
            'color_ids' => $request->get('color_ids'),
            'style_ids' => $request->get('style_ids'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'size_ids' => $request->get('size_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'department_ids' => $request->get('department_ids'),
            'report_type' => (int) $request->get('report_type'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $locationQueries = resolve(LocationQueries::class);
        $location = null;
        if (0 !== $filterData['location_id']) {
            $location = $locationQueries->getByIdWithNameAndCode($companyId, $filterData['location_id']);
        }

        $compareLocation = null;
        if (0 !== $filterData['compare_location_id']) {
            $compareLocation = $locationQueries->getByIdWithNameAndCode(
                $companyId,
                $filterData['compare_location_id'],
            );
        }

        $regionQueries = resolve(RegionQueries::class);
        $region = null;
        if (0 !== $filterData['region_id']) {
            $region = $regionQueries->getById($filterData['region_id'], $companyId);
        }

        $compareRegion = null;
        if (0 !== $filterData['compare_region_id']) {
            $compareRegion = $regionQueries->getById($filterData['compare_region_id'], $companyId);
        }

        $records = $this->getReportDataForPrint($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        return Excel::download(
            new QuantitySoldReportExport(
                $records,
                $location?->name,
                $compareLocation?->name,
                $region?->name ?? 'ALL REGIONS',
                $compareRegion?->name ?? 'ALL REGIONS',
                $filterData['date_range'],
                $company,
                filter_var($filterData['separate_column_sorting'], FILTER_VALIDATE_BOOLEAN)
            ),
            $filename
        );
    }

    public function getReportDataAndConsolidate(array $filterData, int $companyId): array
    {
        $productQueries = resolve(ProductQueries::class);
        $lengthAwarePaginator = null;
        $consolidateData = null;

        if ($filterData['report_type'] === ReportTypes::BY_PARENT_ARTICLE_NUMBER->value) {
            $lengthAwarePaginator = $productQueries->getCachedProductQuantitySoldReportWithArticleNumber(
                $filterData,
                $companyId
            );

            $consolidateData = $productQueries->getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber(
                $filterData,
                $companyId
            );
        }

        if ($filterData['report_type'] === ReportTypes::BY_UPC->value) {
            $lengthAwarePaginator = $productQueries->getCachedProductQuantitySoldReportWithUpc($filterData, $companyId);

            $consolidateData = $productQueries->getCachedConsolidateProductQuantitySoldSumAndCountWithUpc(
                $filterData,
                $companyId
            );
        }

        return [$lengthAwarePaginator, $consolidateData];
    }

    public function getReportDataAndConsolidateWithDifferentSort(array $filterData, int $companyId): array
    {
        $productQueries = resolve(ProductQueries::class);
        $records = null;
        $comparedRecords = null;
        $consolidateData = null;

        if ($filterData['report_type'] === ReportTypes::BY_PARENT_ARTICLE_NUMBER->value) {
            $records = $productQueries->getCachedSingleProductQuantitySoldReportWithArticleNumber(
                $filterData,
                $companyId
            );

            $comparedRecords = $productQueries->getCachedSingleCompareProductQuantitySoldReportWithArticleNumber(
                $filterData,
                $companyId
            );

            $comparedRecordsMissing = array_diff(
                $records->pluck('id')->toArray(),
                $comparedRecords->pluck('id')->toArray()
            );
            $recordsMissing = array_diff($comparedRecords->pluck('id')->toArray(), $records->pluck('id')->toArray());

            foreach ($comparedRecordsMissing as $comparedRecordMissing) {
                $record = $records->where('id', $comparedRecordMissing)->first();

                $record->push([
                    'compare_total_quantity_returned' => 0,
                    'compare_total_returned_amount' => 0,
                    'compare_total_quantity_sold' => 0,
                    'compare_total_amount_sold' => 0,
                ]);

                $comparedRecords->push($record);
            }

            foreach ($recordsMissing as $recordMissing) {
                $comparedRecord = $comparedRecords->where('id', $recordMissing)->first();

                $comparedRecord->push([
                    'compare_total_quantity_returned' => 0,
                    'compare_total_returned_amount' => 0,
                    'compare_total_quantity_sold' => 0,
                    'compare_total_amount_sold' => 0,
                ]);

                $records->push($comparedRecord);
            }

            $consolidateData = $productQueries->getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber(
                $filterData,
                $companyId
            );
        }

        if ($filterData['report_type'] === ReportTypes::BY_UPC->value) {
            $records = $productQueries->getCachedSingleProductQuantitySoldReportWithUpc($filterData, $companyId);

            $comparedRecords = $productQueries->getCachedSingleCompareProductQuantitySoldReportWithUpc(
                $filterData,
                $companyId
            );

            $comparedRecordsMissing = array_diff(
                $records->pluck('id')->toArray(),
                $comparedRecords->pluck('id')->toArray()
            );
            $recordsMissing = array_diff($comparedRecords->pluck('id')->toArray(), $records->pluck('id')->toArray());

            foreach ($comparedRecordsMissing as $comparedRecordMissing) {
                $record = $records->where('id', $comparedRecordMissing)->first();

                $record->push([
                    'compare_total_quantity_returned' => 0,
                    'compare_total_returned_amount' => 0,
                    'compare_total_quantity_sold' => 0,
                    'compare_total_amount_sold' => 0,
                ]);

                $comparedRecords->push($record);
            }

            foreach ($recordsMissing as $recordMissing) {
                $comparedRecord = $comparedRecords->where('id', $recordMissing)->first();

                $comparedRecord->push([
                    'compare_total_quantity_returned' => 0,
                    'compare_total_returned_amount' => 0,
                    'compare_total_quantity_sold' => 0,
                    'compare_total_amount_sold' => 0,
                ]);

                $records->push($comparedRecord);
            }

            $consolidateData = $productQueries->getCachedConsolidateProductQuantitySoldSumAndCountWithUpc(
                $filterData,
                $companyId
            );
        }

        return [$records, $comparedRecords, $consolidateData];
    }

    public function getReportDataForPrint(array $filterData, int $companyId): array
    {
        $productQueries = resolve(ProductQueries::class);
        $records = null;

        if ($filterData['report_type'] === ReportTypes::BY_PARENT_ARTICLE_NUMBER->value) {
            if (filter_var($filterData['separate_column_sorting'], FILTER_VALIDATE_BOOLEAN)) {
                $records = $productQueries->getCachedSingleProductQuantitySoldReportWithArticleNumberCollection(
                    $filterData,
                    $companyId
                );

                $comparedRecords = $productQueries->getCachedSingleComparedProductQuantitySoldReportWithArticleNumberCollection(
                    $filterData,
                    $companyId
                );

                $comparedRecordsMissing = array_diff(
                    $records->pluck('id')->toArray(),
                    $comparedRecords->pluck('id')->toArray()
                );
                $recordsMissing = array_diff(
                    $comparedRecords->pluck('id')->toArray(),
                    $records->pluck('id')->toArray()
                );

                foreach ($comparedRecordsMissing as $comparedRecordMissing) {
                    $record = $records->where('id', $comparedRecordMissing)->first();

                    $record->push([
                        'compare_total_quantity_returned' => 0,
                        'compare_total_returned_amount' => 0,
                        'compare_total_quantity_sold' => 0,
                        'compare_total_amount_sold' => 0,
                    ]);

                    $comparedRecords->push($record);
                }

                foreach ($recordsMissing as $recordMissing) {
                    $comparedRecord = $comparedRecords->where('id', $recordMissing)->first();

                    $comparedRecord->push([
                        'compare_total_quantity_returned' => 0,
                        'compare_total_returned_amount' => 0,
                        'compare_total_quantity_sold' => 0,
                        'compare_total_amount_sold' => 0,
                    ]);

                    $records->push($comparedRecord);
                }

                return [
                    'records' => AdminQuantitySoldReportResource::collection($records)->toArray(new Request()),
                    'compareRecords' => AdminQuantitySoldReportResource::collection($comparedRecords)->toArray(
                        new Request()
                    ),
                ];
            }

            $records = $productQueries->getCachedProductQuantitySoldReportWithArticleNumberCollection(
                $filterData,
                $companyId
            );
        }

        if ($filterData['report_type'] === ReportTypes::BY_UPC->value) {
            if (filter_var($filterData['separate_column_sorting'], FILTER_VALIDATE_BOOLEAN)) {
                $records = $productQueries->getCachedSingleProductQuantitySoldReportWithUpcCollection(
                    $filterData,
                    $companyId
                );

                $comparedRecords = $productQueries->getCachedSingleComparedProductQuantitySoldReportWithUpcCollection(
                    $filterData,
                    $companyId
                );

                $comparedRecordsMissing = array_diff(
                    $records->pluck('id')->toArray(),
                    $comparedRecords->pluck('id')->toArray()
                );
                $recordsMissing = array_diff(
                    $comparedRecords->pluck('id')->toArray(),
                    $records->pluck('id')->toArray()
                );

                foreach ($comparedRecordsMissing as $comparedRecordMissing) {
                    $record = $records->where('id', $comparedRecordMissing)->first();

                    $record->push([
                        'compare_total_quantity_returned' => 0,
                        'compare_total_returned_amount' => 0,
                        'compare_total_quantity_sold' => 0,
                        'compare_total_amount_sold' => 0,
                    ]);

                    $comparedRecords->push($record);
                }

                foreach ($recordsMissing as $recordMissing) {
                    $comparedRecord = $comparedRecords->where('id', $recordMissing)->first();

                    $comparedRecord->push([
                        'compare_total_quantity_returned' => 0,
                        'compare_total_returned_amount' => 0,
                        'compare_total_quantity_sold' => 0,
                        'compare_total_amount_sold' => 0,
                    ]);

                    $records->push($comparedRecord);
                }

                return [
                    'records' => AdminQuantitySoldReportResource::collection($records)->toArray(new Request()),
                    'compareRecords' => AdminQuantitySoldReportResource::collection($comparedRecords)->toArray(
                        new Request()
                    ),
                ];
            }

            $records = $productQueries->getCachedProductQuantitySoldReportWithUpcCollection($filterData, $companyId);
        }

        return (array) AdminQuantitySoldReportResource::collection($records)->toArray(new Request());
    }
}
