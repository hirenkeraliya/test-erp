<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\Enums\SalesByPromoterReportExcludeTypes;
use App\Domains\Promoter\Exports\SalesByPromoterExport;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Promoter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesByPromoterController extends Controller
{
    public function __construct(
        protected PromoterQueries $promoterQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        $promoters = $this->promoterQueries->getPromoterList($locationId, $companyId);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getCompanyBrands($companyId);

        $departmentQueries = resolve(DepartmentQueries::class);
        $departments = $departmentQueries->getWithBasicColumns($companyId);

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        $dateRange = [now()->startOfDay()->format('Y-m-d H:i:s'), now()->endOfDay()->format('Y-m-d H:i:s')];

        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        if (! $validator->fails() && $request->input('date')) {
            $dateRange = $this->getDateRange($request->input('date'), $request->input('type'));
        }

        return Inertia::render('sales/sales_by_promoters/Index', [
            'promoters' => $promoters,
            'brands' => $brands,
            'departments' => $departments,
            'salesFilterTypes' => SalesByPromoterReportExcludeTypes::formattedForSelection(),
            'defaultSelected' => [SalesByPromoterReportExcludeTypes::VOID_SALE->value],
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId($companyId),
            'exportPermission' => PermissionList::getExportPermissionName('sales_by_promoter'),
            'dashboardFilterData' => [
                'promoter_id' => (int) $request->get('promoter_id'),
                'dateRange' => $dateRange,
                'sort_by' => 'promoter_sale_total.total_amount_sold',
                'sort_direction' => 'desc',
            ],
            'helpCenterMessages' => 'Analyze sales performance by promoter with customizable filter options, robust search capabilities, and seamless export functionality to digging deeper into your data insights.',
        ]);
    }

    public function fetchSalesByPromoters(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'promoter_id' => $request->get('promoter_id'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'department_ids' => $request->get('department_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'group_ids' => $request->get('group_ids'),
            'sales_filter_types' => $request->get('sales_filter_types') ?? [],
        ];

        $lengthAwarePaginator = $this->promoterQueries->getPaginatedSalesByPromoters(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        /** @var Promoter $promoterTotals */
        $promoterTotals = $this->promoterQueries->getSalesByPromotersTotals(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $totalUnitSold = (float) ($promoterTotals['total_units_sold'] - $promoterTotals['total_units_returned']);

        $lengthAwarePaginator->transform(fn ($promoter): array => $this->preparedRecords($promoter, $totalUnitSold));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
            'total_net_sales' => $promoterTotals['total_amount_sold'] - $promoterTotals['total_returned_amount'],
            'total_sales' => $promoterTotals['total_amount_sold'] ?? 0,
            'total_units_sold' => $promoterTotals['total_units_sold'] ?? 0,
            'total_units_returned' => $promoterTotals['total_units_returned'] ?? 0,
            'total_returned_amount' => $promoterTotals['total_returned_amount'] ?? 0,
        ];
    }

    public function exportSalesByPromoters(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'promoter_id' => $request->get('promoter_id'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'department_ids' => $request->get('department_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'group_ids' => $request->get('group_ids'),
            'sales_filter_types' => $request->get('sales_filter_types') ?? [],
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $salesByPromoters = $this->promoterQueries->getSalesByPromotersExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        /** @var Promoter $promoterTotals */
        $promoterTotals = $this->promoterQueries->getSalesByPromotersTotals(
            $filterData,
            session('store_manager_selected_location_company_id')
        );
        $totalUnitSold = (float) ($promoterTotals['total_units_sold'] - $promoterTotals['total_units_returned']);

        $salesByPromoters->transform(fn ($promoter): array => $this->preparedRecords($promoter, $totalUnitSold));

        return Excel::download(new SalesByPromoterExport($salesByPromoters, $filteredColumns), $filename);
    }

    private function getDateRange(string $date, ?string $type): array
    {
        if ('yearly' === $type) {
            /** @var Carbon $selectedDate */
            $selectedDate = Carbon::createFromFormat('Y-m-d', $date);

            return [
                CommonFunctions::addStartTime($selectedDate->startOfYear()->format('Y-m-d')),
                CommonFunctions::addEndTime($date),
            ];
        }

        return [CommonFunctions::addStartTime($date), CommonFunctions::addEndTime($date)];
    }

    private function preparedRecords(Promoter $promoter, float $totalUnitSold): array
    {
        /** @var Employee $employee */
        $employee = $promoter->employee;

        $promoterGroup = $promoter->promoterGroup;
        $netAmount = ($promoter['total_amount_sold'] - $promoter['total_returned_amount']);
        $grossAmount = ($promoter['total_amount_sold'] + $promoter['total_discount_amount'] - $promoter['total_tax_amount']);
        $averageTransactionValue = $promoter['total_sales'] > 0 ? ($netAmount / $promoter['total_sales']) : 0;
        $unitsPerTransaction = $promoter['total_sales'] > 0 ? ($promoter['total_units_sold'] / $promoter['total_sales']) : 0;
        $perSalesWithStaffHelp = $totalUnitSold > 0.0 ? CommonFunctions::numberFormat(
            ($promoter['total_units_sold'] - $promoter['total_units_returned']) * 100 / $totalUnitSold
        ) : 0;

        return [
            'promoter' => $employee->getFullName() . '(' . $employee->staff_id . ')',
            'locations' => implode(',', $promoter->locations->pluck('name')->toArray()),
            'promoter_group' => $promoterGroup ? $promoterGroup->name : '',
            'units_sold' => $promoter['total_units_sold'] ?? 0,
            'units_returned' => $promoter['total_units_returned'] ?? 0,
            'return_amount' => $promoter['total_returned_amount'] ?? 0,
            'gross_amount' => CommonFunctions::numberFormat($grossAmount),
            'discount_amount' => $promoter['total_discount_amount'] ?? 0,
            'tax_amount' => $promoter['total_tax_amount'] ?? 0,
            'net_amount' => $netAmount,
            'average_transaction_value' => CommonFunctions::numberFormat($averageTransactionValue),
            'units_per_transaction' => CommonFunctions::numberFormat($unitsPerTransaction),
            'per_sales_with_staff_help' => CommonFunctions::numberFormat($perSalesWithStaffHelp),
        ];
    }
}
