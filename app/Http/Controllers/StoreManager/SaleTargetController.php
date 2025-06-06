<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Exports\SaleTargetExport;
use App\Domains\SaleTarget\Resources\SaleTargetViewResource;
use App\Domains\SaleTarget\Resources\StoreManagerSaleTargetListResource;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleTargetController extends Controller
{
    public function __construct(
        protected SaleTargetQueries $saleTargetQueries
    ) {
    }

    public function index(): Response
    {
        $promoters = $this->fetchPromoters(session('store_manager_selected_location_id'));

        return Inertia::render('sale_targets/Index', [
            'targetTypes' => $this->targetTypes(),
            'timeIntervalTypes' => TimeIntervalType::getList(),
            'status' => Statuses::getList(),
            'promoters' => $promoters,
            'staticTargetTypes' => TargetType::getFormattedArrayForStaticUse(),
            'exportPermission' => PermissionList::getExportPermissionName('sale_target'),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchSaleTargets(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'target_type' => $request->get('target_type'),
            'time_interval_type' => $request->get('time_interval_type'),
            'select_status' => $request->get('select_status'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'promoter_ids' => $request->get('promoter_ids'),
        ];
        $lengthAwarePaginator = $this->saleTargetQueries->listQuery(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => StoreManagerSaleTargetListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportSaleTargets(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'target_type' => $request->get('target_type'),
            'time_interval_type' => $request->get('time_interval_type'),
            'select_status' => $request->get('select_status'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'promoter_ids' => $request->get('promoter_ids'),
        ];

        $saleTargets = $this->saleTargetQueries->getSaleTargetExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new SaleTargetExport($saleTargets), $filename);
    }

    public function targetTypes(): array
    {
        $getAllTargetType = TargetType::getList();
        $companyWiseTargetType = TargetType::COMPANY_WISE->value;

        return array_values(
            array_filter($getAllTargetType, fn (array $value): bool => $value['id'] != $companyWiseTargetType)
        );
    }

    public function fetchSaleTarget(int $saleTargetId): array
    {
        $saleTarget = $this->saleTargetQueries->getById(
            $saleTargetId,
            session('store_manager_selected_location_company_id')
        );

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId(session('store_manager_selected_location_company_id'));

        return [
            'sale_target_details' => new SaleTargetViewResource($saleTarget, $currency->getSymbol()),
        ];
    }

    private function fetchPromoters(int $locationId): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);

        $promoters = $promoterQueries->getPromoterList(
            $locationId,
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

        return $promoters;
    }
}
