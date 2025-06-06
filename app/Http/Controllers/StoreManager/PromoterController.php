<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\FilterStatus;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\DataObjects\ChangePasswordData;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Domains\Promoter\Exports\PromoterExport;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\Resources\StoreManagerPromoterListResource;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PromoterController extends Controller
{
    public function __construct(
        protected PromoterQueries $promoterQueries
    ) {
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getStorePromoters(): array
    {
        $promoters = $this->promoterQueries->getPromoterListOfSelectedStore(
            session('store_manager_selected_location_id')
        );

        return [
            'promoters' => StoreManagerPromoterListResource::collection($promoters),
        ];
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getStoreActivePromoters(): array
    {
        $promoters = $this->promoterQueries->getActivePromoterListOfSelectedStore(
            session('store_manager_selected_location_id')
        );

        return [
            'promoters' => StoreManagerPromoterListResource::collection($promoters),
        ];
    }

    public function index(): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('promoters/Index', [
            'locations' => $locations,
            'statusActive' => FilterStatus::ACTIVE->value,
            'promoterStatuses' => FilterStatus::getList(),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId($companyId),
            'exportPermission' => PermissionList::getExportPermissionName('promoter'),
        ]);
    }

    public function fetchPromoters(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'group_ids' => $request->get('group_ids'),
            'status' => (int) $request->get('status'),
        ];

        $lengthAwarePaginator = $this->promoterQueries->listQuery(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        [$locations, $employees] = $this->fetchCommonRecords($companyId);

        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);

        return Inertia::render('promoters/Manage', [
            'locations' => $locations,
            'employees' => $employees,
            'company' => $company,
            'commissionTypes' => CommissionTypes::toArray(),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId($companyId),
        ]);
    }

    public function store(PromoterData $promoterData, Request $request): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('store_manager_selected_location_company_id'), $promoterData);

        DB::beginTransaction();

        try {
            /** @var StoreManager $user */
            $user = $request->user();

            $this->promoterQueries->addNew($promoterData, $user);

            DB::commit();

            return to_route('store_manager.promoters.index')
                ->with('success', 'Promoter added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Promoter', [
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

    public function edit(int $promoterId): Response
    {
        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails(
            session('store_manager_selected_location_company_id')
        );

        $promoter = $this->promoterQueries->getByIdWithEmployeeAndLocations(
            $promoterId,
            session('store_manager_selected_location_company_id')
        );

        [$locations, $employees] = $this->fetchCommonRecords(session('store_manager_selected_location_company_id'));

        return Inertia::render('promoters/Manage', [
            'promoter' => $promoter,
            'company' => $company,
            'locations' => $locations,
            'employees' => $employees,
            'commissionTypes' => CommissionTypes::toArray(),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId(
                session('store_manager_selected_location_company_id')
            ),
        ]);
    }

    public function update(PromoterData $promoterData, int $promoterId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(
            session('store_manager_selected_location_company_id'),
            $promoterData,
            $promoterId
        );

        DB::beginTransaction();

        try {
            $this->promoterQueries->update(
                $promoterData,
                $promoterId,
                session('store_manager_selected_location_company_id')
            );

            DB::commit();

            return to_route('store_manager.promoters.index')
                ->with('success', 'Promoter updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Promoter', [
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

    public function exportPromoters(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'group_ids' => $request->get('group_ids'),
            'status' => (int) $request->get('status'),
        ];

        $promoters = $this->promoterQueries->getPromotersExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new PromoterExport($promoters), $filename);
    }

    public function changePassword(int $promoterId): Response
    {
        return Inertia::render('promoters/ChangePassword', [
            'promoterId' => $promoterId,
        ]);
    }

    public function updatePassword(ChangePasswordData $changePasswordData, int $promoterId): RedirectResponse
    {
        $promoter = $this->promoterQueries->getById($promoterId, session('store_manager_selected_location_company_id'));

        $this->promoterQueries->changePassword($promoter, $changePasswordData);

        return to_route('store_manager.promoters.index')
            ->with('success', 'Password updated successfully.');
    }

    private function validateSelectedRecordsWithCompany(
        int $companyId,
        PromoterData $promoterData,
        ?int $promoterId = null
    ): void {
        $locationQueries = resolve(LocationQueries::class);

        $allStoresExist = $locationQueries->doAllStoresExist($companyId, $promoterData->location_ids);

        if (! $allStoresExist) {
            throw new RedirectWithErrorException(
                'store_manager.promoters.index',
                'One of the selected stores does not match the current company.'
            );
        }

        if (null === $promoterData->code) {
            return;
        }

        $promoterQueries = resolve(PromoterQueries::class);
        if ($promoterQueries->doesCodeExist($promoterData->code, $companyId, $promoterId)) {
            throw new RedirectWithErrorException(
                'store_manager.promoters.index',
                'Specified promoter code has already been taken.'
            );
        }
    }

    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        $employees = $employeeQueries->getFormattedEmployeesOf($companyId);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return [$locations, $employees];
    }
}
