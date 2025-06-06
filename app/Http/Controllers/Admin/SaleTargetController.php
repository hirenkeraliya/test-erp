<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\Statuses;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Domains\SaleTarget\DataObjects\SaleTargetData;
use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\SaleTargetPromoterTypes;
use App\Domains\SaleTarget\Enums\SaleTargetStoreTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Exports\SaleTargetExport;
use App\Domains\SaleTarget\Jobs\SaleAchievedTargetJob;
use App\Domains\SaleTarget\Resources\SaleTargetEditResource;
use App\Domains\SaleTarget\Resources\SaleTargetListResource;
use App\Domains\SaleTarget\Resources\SaleTargetViewResource;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SaleTarget\Services\SaleTargetService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SaleTargetController extends Controller
{
    public function __construct(
        protected SaleTargetQueries $saleTargetQueries
    ) {
    }

    public function index(): Response
    {
        [$locations, $promoters] = $this->fetchCommonRecords(session('admin_company_id'));

        return Inertia::render('sale_targets/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('sale_target'),
            'targetTypes' => TargetType::getList(),
            'timeIntervalTypes' => TimeIntervalType::getList(),
            'status' => Statuses::getList(),
            'locations' => $locations,
            'promoters' => $promoters,
            'staticTargetTypes' => TargetType::getFormattedArrayForStaticUse(),
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
            'location_ids' => $request->get('location_ids'),
            'promoter_ids' => $request->get('promoter_ids'),
        ];

        $lengthAwarePaginator = $this->saleTargetQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleTargetListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(Request $request): Response
    {
        [$locations, $promoters, $regions] = $this->fetchCommonRecords(session('admin_company_id'));

        $targetType = $request->get('target_type') ? TargetType::getValueByCaseName(
            Str::of($request->get('target_type'))->replace(' ', '_')->upper()->value()
        ) : null;
        $timeIntervalSelection = $request->get('time_interval_selection') ? TimeIntervalType::getValueByCaseName(
            Str::of($request->get('time_interval_selection'))->replace(' ', '_')->upper()->value()
        ) : null;

        return Inertia::render('sale_targets/Manage', [
            'targetTypes' => TargetType::formattedForSelection(),
            'timeIntervalTypes' => TimeIntervalType::formattedForSelection(),
            'locations' => $locations,
            'promoters' => $promoters,
            'regions' => $regions,
            'staticTargetTypes' => TargetType::getFormattedArrayForStaticUse(),
            'staticTimeIntervalTypes' => TimeIntervalType::getFormattedArrayForStaticUse(),
            'saleTargetAmountTypes' => SaleTargetAmountTypes::getFormattedArrayForStaticUse(),
            'saleTargetStoreTypes' => SaleTargetStoreTypes::getFormattedArrayForStaticUse(),
            'saleTargetPromoterTypes' => SaleTargetPromoterTypes::getFormattedArrayForStaticUse(),
            'targetType' => $targetType,
            'timeIntervalSelection' => $timeIntervalSelection,
        ]);
    }

    public function store(SaleTargetData $saleTargetData): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $saleTargetService = resolve(SaleTargetService::class);
            $saleTarget = $saleTargetService->addSaleTarget($saleTargetData, session('admin_company_id'));

            DB::commit();

            SaleAchievedTargetJob::dispatch($saleTarget->id)->onQueue('medium');

            return to_route('admin.sale_targets.index')->with('success', 'Sale Targets added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Sale-Target', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function fetchSaleTarget(int $saleTargetId): array
    {
        $saleTarget = $this->saleTargetQueries->getById($saleTargetId, session('admin_company_id'));

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId(session('admin_company_id'));

        return [
            'sale_target_details' => new SaleTargetViewResource($saleTarget, $currency->getSymbol()),
        ];
    }

    public function edit(int $saleTargetId): Response
    {
        $saleTarget = $this->saleTargetQueries->getById($saleTargetId, session('admin_company_id'));

        [$locations, $promoters, $regions] = $this->fetchCommonRecords(session('admin_company_id'));

        return Inertia::render('sale_targets/Manage', [
            'saleTarget' => (new SaleTargetEditResource($saleTarget))->jsonSerialize(),
            'targetTypes' => TargetType::formattedForSelection(),
            'timeIntervalTypes' => TimeIntervalType::formattedForSelection(),
            'locations' => $locations,
            'promoters' => $promoters,
            'regions' => $regions,
            'staticTargetTypes' => TargetType::getFormattedArrayForStaticUse(),
            'staticTimeIntervalTypes' => TimeIntervalType::getFormattedArrayForStaticUse(),
            'saleTargetAmountTypes' => SaleTargetAmountTypes::getFormattedArrayForStaticUse(),
            'saleTargetStoreTypes' => SaleTargetStoreTypes::getFormattedArrayForStaticUse(),
            'saleTargetPromoterTypes' => SaleTargetPromoterTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(SaleTargetData $saleTargetData, int $saleTargetId): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $saleTargetService = resolve(SaleTargetService::class);
            $saleTargetService->updateSaleTarget($saleTargetData, $saleTargetId, session('admin_company_id'));

            DB::commit();

            SaleAchievedTargetJob::dispatch($saleTargetId)->onQueue('medium');

            return to_route('admin.sale_targets.index')->with('success', 'Sale Target updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Sale-Target', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
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
            'location_ids' => $request->get('location_ids'),
            'promoter_ids' => $request->get('promoter_ids'),
        ];

        $saleTargets = $this->saleTargetQueries->getSaleTargetExport($filterData, session('admin_company_id'));

        return Excel::download(new SaleTargetExport($saleTargets), $filename);
    }

    public function setStatus(int $saleTargetId, bool $status): RedirectResponse
    {
        $this->saleTargetQueries->adminSetStatus($saleTargetId, session('admin_company_id'), $status);

        return to_route('admin.sale_targets.index')->with('success', 'Status changed successfully.');
    }

    public function reGenerateTarget(int $saleTargetId): void
    {
        $this->saleTargetQueries->markAsRegenerateStart($saleTargetId, session('admin_company_id'));

        $saleAchievedTargetQueries = resolve(SaleAchievedTargetQueries::class);
        $saleAchievedTargetQueries->deleteSaleAchievedTargetFromSaleTarget($saleTargetId);

        SaleAchievedTargetJob::dispatch($saleTargetId)->onQueue('medium');
    }

    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $regionQueries = resolve(RegionQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $promoters = $promoterQueries->getAllPromoterByCompany($companyId);
        $regions = $regionQueries->getWithBasicColumns($companyId);

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        return [$locations, $promoters, $regions];
    }
}
