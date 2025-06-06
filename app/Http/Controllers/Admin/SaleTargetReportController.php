<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleAchievedTarget\Exports\SaleAchievedTargetExport;
use App\Domains\SaleAchievedTarget\Resources\SaleTargetAchievedListResource;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTargetTimeframe\SaleTargetTimeframeQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleTargetReportController extends Controller
{
    public function __construct(
        protected SaleAchievedTargetQueries $saleAchievedTargetQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getAllPromoterByCompany($companyId);

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        $targetTypes = null;
        $timeIntervalType = null;

        if ($request->get('target_type')) {
            $targetTypes = TargetType::getValueByCaseName($request->get('target_type'));
        }

        if ($request->get('time_interval_selection')) {
            $timeIntervalType = TimeIntervalType::getValueByCaseName(
                Str::of($request->get('time_interval_selection'))->title()->value()
            );
        }

        [$day, $week, $month, $year] = $this->getTimeframeData($request, $timeIntervalType);

        return Inertia::render('reports/sale_targets/Index', [
            'locations' => $locations,
            'promoters' => $promoters,
            'exportPermission' => PermissionList::getExportPermissionName('sale_achieved_target'),
            'targetTypes' => TargetType::getList(),
            'staticTargetTypes' => TargetType::getFormattedArrayForStaticUse(),
            'timeframeTypes' => TimeIntervalType::getList(),
            'staticTimeframeTypes' => TimeIntervalType::getFormattedArrayForStaticUse(),
            'filterData' => [
                'target_type' => $targetTypes,
                'time_interval_type' => $timeIntervalType,
                'timeframe_id' => $request->get('timeframe_id') ?? null,
                'day' => $day,
                'week' => $week,
                'month' => $month,
                'year' => $year,
            ],
            'helpCenterMessages' => 'Show all the sale achieved targets with target types, target value and achieved value and offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    private function getTimeframeData(Request $request, ?int $timeIntervalType): array
    {
        $day = [];
        $week = [];
        $month = '';
        $year = '';

        if ($request->get('timeframe_ids') && $timeIntervalType) {
            $saleTargetTimeframeQueries = resolve(SaleTargetTimeframeQueries::class);
            $saleTargetTimeframes = $saleTargetTimeframeQueries->getByIds(
                $request->get('timeframe_ids'),
                session('admin_company_id'));

            if ($timeIntervalType === TimeIntervalType::DAILY->value) {
                $day = [$saleTargetTimeframes->min('start_date'), $saleTargetTimeframes->max('end_date')];
            }

            if ($timeIntervalType === TimeIntervalType::WEEKLY->value) {
                $week = [$saleTargetTimeframes->max('start_date'), $saleTargetTimeframes->max('end_date')];
            }

            if ($timeIntervalType === TimeIntervalType::MONTHLY->value) {
                $date = Carbon::parse($saleTargetTimeframes->max('start_date'));
                $month = [
                    'month' => ($date->format('m') - 1),
                    'year' => $date->format('Y'),
                ];
            }

            if ($timeIntervalType === TimeIntervalType::YEARLY->value) {
                $year = $saleTargetTimeframes->target_label ?? null;
            }
        }

        return [$day, $week, $month, $year];
    }

    public function fetchSaleAchievedTargets(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'promoter_ids' => $request->get('promoter_ids'),
            'location_ids' => $request->get('location_ids'),
            'time_interval_type' => (int) $request->get('time_interval_type'),
            'date_range' => (array) $request->get('date_range'),
            'week' => (array) $request->get('week'),
            'year' => $request->get('year'),
            'month' => (array) $request->get('month'),
            'target_type' => $request->get('target_type'),
        ];

        $lengthAwarePaginator = $this->saleAchievedTargetQueries->getPaginatedSaleTargetAchievedList(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleTargetAchievedListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportSaleAchievedTarget(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'promoter_ids' => $request->get('promoter_ids'),
            'location_ids' => $request->get('location_ids'),
            'time_interval_type' => (int) $request->get('time_interval_type'),
            'date_range' => (array) $request->get('date_range'),
            'week' => (array) $request->get('week'),
            'year' => $request->get('year'),
            'month' => (array) $request->get('month'),
            'target_type' => $request->get('target_type'),
        ];

        $saleAchievedTargets = $this->saleAchievedTargetQueries->getSaleAchievedTargetForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new SaleAchievedTargetExport($saleAchievedTargets), $filename);
    }

    /**
     * @return array<string, Collection>
     */
    public function getStoreWisePromoters(Request $request): array
    {
        $locationIds = $request->get('location_ids');
        $promoterQueries = resolve(PromoterQueries::class);

        $promoters = $promoterQueries->getPromoterByLocations($locationIds);

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        return [
            'promoters' => $promoters,
        ];
    }

    public function getSalesAndSalesReturnsForSaleAchievedTarget(int $saleAchievedTargetId): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        $saleAchievedTarget = $this->saleAchievedTargetQueries->getByIdWithSaleTargetAndTimeframe(
            $saleAchievedTargetId,
            session('admin_company_id')
        );

        /** @var SaleTargetTimeframe $saleTargetTimeframe */
        $saleTargetTimeframe = $saleAchievedTarget->saleTargetTimeframe;

        /** @var SaleTarget $saleTarget */
        $saleTarget = $saleTargetTimeframe->saleTarget;

        /** @var Collection $locations */
        $locations = $saleTarget->locations;

        /** @var Collection $promoters */
        $promoters = $saleTarget->promoters;

        $sales = $saleQueries->getAchievedSaleTargetSales(
            [$saleTargetTimeframe->start_date, $saleTargetTimeframe->end_date],
            session('admin_company_id'),
            $locations->pluck('id')->toArray(),
            $promoters->pluck('id')->toArray(),
        );

        $saleReturns = $saleReturnQueries->getAchievedSaleTargetSaleReturn(
            [$saleTargetTimeframe->start_date, $saleTargetTimeframe->end_date],
            session('admin_company_id'),
            $locations->pluck('id')->toArray(),
            $promoters->pluck('id')->toArray(),
        );

        return [
            'sales' => $sales,
            'sale_returns' => $saleReturns,
        ];
    }
}
