<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\FilterStatus;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\DataObjects\ChangePasswordData;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Domains\Promoter\Exports\PromoterBulkUpdateExport;
use App\Domains\Promoter\Exports\PromoterExport;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommissionRegeneration\Jobs\PromoterCommissionRegenerationJob;
use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Http\Resources\app\Domains\Promoter\PromoterListResource;
use App\Models\Admin;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $promoterGroupQueries = resolve(PromoterGroupQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $promoterCommissionRegenerationQueries = resolve(PromoterCommissionRegenerationQueries::class);
        $startOfPreviousMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $isPromoterCommissionRegenerationRunning = $promoterCommissionRegenerationQueries->entryExistsForPeriod(
            $startOfPreviousMonth
        );

        return Inertia::render('promoters/Index', [
            'locations' => $locations,
            'statusActive' => FilterStatus::ACTIVE->value,
            'promoterStatuses' => FilterStatus::getList(),
            'isPromoterCommissionRegenerationRunning' => $isPromoterCommissionRegenerationRunning,
            'regenerateCommissionButtonText' => 'Regenerate commission for ' . now()->subMonthNoOverflow()->format(
                'M Y'
            ),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId(session('admin_company_id')),
            'exportPermission' => PermissionList::getExportPermissionName('promoter'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
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

        $lengthAwarePaginator = $this->promoterQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        [$locations, $employees] = $this->fetchCommonRecords(session('admin_company_id'));

        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails(session('admin_company_id'));

        return Inertia::render('promoters/Manage', [
            'locations' => $locations,
            'employees' => $employees,
            'company' => $company,
            'commissionTypes' => CommissionTypes::toArray(),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId(session('admin_company_id')),
        ]);
    }

    public function store(PromoterData $promoterData, Request $request): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $promoterData);

        DB::beginTransaction();

        try {
            /** @var Admin $user */
            $user = $request->user();

            $this->promoterQueries->addNew($promoterData, $user);

            DB::commit();

            return to_route('admin.promoters.index')
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
        $company = $companyQueries->getByIdWithPromoterCommissionDetails(session('admin_company_id'));

        $promoter = $this->promoterQueries->getByIdWithEmployeeAndLocations($promoterId, session('admin_company_id'));

        [$locations, $employees] = $this->fetchCommonRecords(session('admin_company_id'));

        return Inertia::render('promoters/Manage', [
            'promoter' => $promoter,
            'company' => $company,
            'locations' => $locations,
            'employees' => $employees,
            'commissionTypes' => CommissionTypes::toArray(),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId(session('admin_company_id')),
        ]);
    }

    public function update(PromoterData $promoterData, int $promoterId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $promoterData, $promoterId);

        DB::beginTransaction();

        try {
            $this->promoterQueries->update($promoterData, $promoterId, session('admin_company_id'));

            DB::commit();

            return to_route('admin.promoters.index')
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

        $promoters = $this->promoterQueries->getPromotersExport($filterData, session('admin_company_id'));

        return Excel::download(new PromoterExport($promoters), $filename);
    }

    public function regenerateCommission(Request $request): RedirectResponse
    {
        $reason = $request->reason;

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'reason' => ['required', 'string'],
        ]);

        $startOfPreviousMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $promoterCommissionRegenerationQueries = resolve(PromoterCommissionRegenerationQueries::class);
        if ($promoterCommissionRegenerationQueries->entryExistsForPeriod($startOfPreviousMonth)) {
            return back()->with(
                'error',
                'Your request for Commission Regeneration is currently being processed in the background. Please wait for the process to complete.'
            );
        }

        $superAdminQueries = resolve(SuperAdminQueries::class);
        $superAdmin = $superAdminQueries->getByUsername($credentials['username']);
        if (! $superAdmin) {
            return back()->withErrors([
                'username' => ['The provided username does not match our records.'],
                'password' => ['The provided password does not match our records.'],
            ]);
        }

        if (! Hash::check($credentials['password'], $superAdmin->password)) {
            return back()->withErrors([
                'username' => ['The provided username does not match our records.'],
                'password' => ['The provided password does not match our records.'],
            ]);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $promoterCommissionRegeneration = $promoterCommissionRegenerationQueries->addNew([
            'period' => $startOfPreviousMonth,
            'admin_id' => $admin->id,
            'super_admin_id' => $superAdmin->id,
            'reason' => $reason,
        ]);

        PromoterCommissionRegenerationJob::dispatch($promoterCommissionRegeneration->id)->onQueue('high');

        return back()->with(
            'success',
            'Your Commission Regenerate request has been sent successfully. The Regeneration process will now commence in the background. Kindly allow some time for the process to complete.'
        );
    }

    public function getByLocationIds(Request $request): array
    {
        $locationIds = $request->get('location_ids');

        if (! $locationIds) {
            return [
                'promoters' => [],
            ];
        }

        $promoters = $this->promoterQueries->getPromoterByLocations($locationIds);

        return [
            'promoters' => PromoterListResource::collection($promoters),
        ];
    }

    public function getActivePromoterByLocationIds(Request $request): array
    {
        $locationIds = $request->get('location_ids');

        if (! $locationIds) {
            return [
                'promoters' => [],
            ];
        }

        $promoters = $this->promoterQueries->getActivePromoterByLocations($locationIds);

        return [
            'promoters' => PromoterListResource::collection($promoters),
        ];
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getStorePromoters(int $locationId): array
    {
        $promoters = $this->promoterQueries->getPromoterListOfSelectedStore($locationId);

        return [
            'promoters' => PromoterListResource::collection($promoters),
        ];
    }

    public function changePassword(int $promoterId): Response
    {
        return Inertia::render('promoters/ChangePassword', [
            'promoterId' => $promoterId,
        ]);
    }

    public function updatePassword(ChangePasswordData $changePasswordData, int $promoterId): RedirectResponse
    {
        $promoter = $this->promoterQueries->getById($promoterId, session('admin_company_id'));

        $this->promoterQueries->changePassword($promoter, $changePasswordData);

        return to_route('admin.promoters.index')
            ->with('success', 'Password updated successfully.');
    }

    public function getPromotersOfStaffIds(Request $request): array
    {
        $staffIds = $request->get('staffIds');

        $promoters = $this->promoterQueries->getPromotersOfStaffIds($staffIds, session('admin_company_id'));

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

    public function exportExistingPromoters(): BinaryFileResponse
    {
        $promoters = $this->promoterQueries->getPromoterForBulkUpdate(session('admin_company_id'));
        $companyQueries = resolve(CompanyQueries::class);
        $promoterCommissionType = $companyQueries->getByIdWithPromoterCommissionDetails(session('admin_company_id'));

        return Excel::download(
            new PromoterBulkUpdateExport($promoters, $promoterCommissionType),
            'promoter-bulk-update.xlsx'
        );
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
                'admin.promoters.index',
                'One of the selected stores does not match the current company.'
            );
        }

        if (null === $promoterData->code) {
            return;
        }

        $promoterQueries = resolve(PromoterQueries::class);
        if ($promoterQueries->doesCodeExist($promoterData->code, $companyId, $promoterId)) {
            throw new RedirectWithErrorException(
                'admin.promoters.index',
                'Specified promoter code has already been taken.'
            );
        }
    }

    /**
     * @return array<int, mixed[]>|Collection[]
     */
    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        $employees = $employeeQueries->getFormattedEmployeesOf($companyId);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return [$locations, $employees];
    }
}
