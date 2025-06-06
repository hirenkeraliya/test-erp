<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleAchievedTarget\Exports\SaleAchievedTargetExport;
use App\Domains\SaleAchievedTarget\Resources\SaleTargetAchievedListResource;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function index(): Response
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $promoters = $promoterQueries->getPromoterList(
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        return Inertia::render('reports/sale_targets/Index', [
            'promoters' => $promoters,
            'targetTypes' => $this->targetTypes(),
            'staticTargetTypes' => TargetType::getFormattedArrayForStaticUse(),
            'timeframeTypes' => TimeIntervalType::getList(),
            'staticTimeframeTypes' => TimeIntervalType::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('sale_achieved_target'),
            'helpCenterMessages' => 'Show all the sale achieved targets with target types, target value and achieved value and offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetchSaleAchievedTargets(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'promoter_ids' => $request->get('promoter_ids'),
            'time_interval_type' => (int) $request->get('time_interval_type'),
            'date_range' => (array) $request->get('date_range'),
            'week' => (array) $request->get('week'),
            'year' => $request->get('year'),
            'month' => (array) $request->get('month'),
            'target_type' => $request->get('target_type'),
        ];

        $lengthAwarePaginator = $this->saleAchievedTargetQueries->getPaginatedSaleTargetAchievedListForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
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
            'time_interval_type' => (int) $request->get('time_interval_type'),
            'date_range' => (array) $request->get('date_range'),
            'week' => (array) $request->get('week'),
            'year' => $request->get('year'),
            'month' => (array) $request->get('month'),
            'target_type' => $request->get('target_type'),
        ];

        $saleAchievedTargets = $this->saleAchievedTargetQueries->getSaleAchievedTargetExportForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new SaleAchievedTargetExport($saleAchievedTargets), $filename);
    }

    public function targetTypes(): array
    {
        $getAllTargetType = TargetType::getList();
        $companyWiseTargetType = TargetType::COMPANY_WISE->value;

        return array_values(
            array_filter($getAllTargetType, fn (array $value): bool => $value['id'] != $companyWiseTargetType)
        );
    }

    public function getSalesAndSalesReturnsForSaleAchievedTarget(int $saleAchievedTargetId): array
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        $saleAchievedTarget = $this->saleAchievedTargetQueries->getByIdWithSaleTargetAndTimeframe(
            $saleAchievedTargetId,
            session('store_manager_selected_location_company_id')
        );

        /** @var SaleTargetTimeframe $saleTargetTimeframe */
        $saleTargetTimeframe = $saleAchievedTarget->saleTargetTimeframe;

        /** @var SaleTarget $saleTarget */
        $saleTarget = $saleTargetTimeframe->saleTarget;

        /** @var Collection $promoters */
        $promoters = $saleTarget->promoters;

        $sales = $saleQueries->getAchievedSaleTargetSales(
            [$saleTargetTimeframe->start_date, $saleTargetTimeframe->end_date],
            session('store_manager_selected_location_company_id'),
            [session('store_manager_selected_store_id')],
            $promoters->pluck('id')->toArray(),
        );

        $saleReturns = $saleReturnQueries->getAchievedSaleTargetSaleReturn(
            [$saleTargetTimeframe->start_date, $saleTargetTimeframe->end_date],
            session('store_manager_selected_location_company_id'),
            [session('store_manager_selected_store_id')],
            $promoters->pluck('id')->toArray(),
        );

        return [
            'sales' => $sales,
            'sale_returns' => $saleReturns,
        ];
    }
}
