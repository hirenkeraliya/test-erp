<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\Employee\Exports\EmployeeExport;
use App\Domains\Employee\Resources\EmployeeFilterListResource;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\Permission\Enums\PermissionList;
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

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeQueries $employeeQueries
    ) {
    }

    /**
     * @return array<string, AnonymousResourceCollection>
     */
    public function getFilteredEmployees(Request $request): array
    {
        $employeeSearch = $this->employeeQueries->searchEmployeesForFilter(
            $request->input('search_text'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'employees' => EmployeeFilterListResource::collection($employeeSearch),
        ];
    }

    public function index(): Response
    {
        return Inertia::render('employees/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('employee'),
        ]);
    }

    public function fetchEmployees(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->employeeQueries->adminListQuery(
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
        $designationQueries = resolve(DesignationQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);
        $companyId = session('store_manager_selected_location_company_id');

        return Inertia::render('employees/Manage', [
            'jobTypes' => JobTypes::formattedForSelection(),
            'companyId' => $companyId,
            'designations' => $designationQueries->getByCompanyId($companyId),
            'employeeGroups' => $employeeGroupQueries->getByCompanyId($companyId),
        ]);
    }

    public function store(EmployeeData $employeeData, Request $request): RedirectResponse
    {
        $this->checkRequestDetails($employeeData);

        DB::beginTransaction();

        try {
            /** @var StoreManager $user */
            $user = $request->user();

            $employee = $this->employeeQueries->addNew($employeeData, $user);

            $memberService = resolve(MemberService::class);
            $memberService->addNewEmployeeMember($employee);

            DB::commit();

            return to_route('store_manager.employees.index')
                ->with('success', 'Employee added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Employee Store Manager', [
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

    public function setStatus(int $employeeId, bool $status, Request $request): RedirectResponse
    {
        /** @var StoreManager $user */
        $user = $request->user();

        $companyId = session('store_manager_selected_location_company_id');

        $this->employeeQueries->adminSetStatus($employeeId, $companyId, $status, $user);

        return to_route('store_manager.employees.index')->with('success', 'Status changed successfully.');
    }

    public function edit(int $employeeId): Response|RedirectResponse
    {
        $employee = $this->employeeQueries->getByIdWithMedia($employeeId);
        $designationQueries = resolve(DesignationQueries::class);
        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        if (session('store_manager_selected_location_company_id') !== $employee->getCompanyId()) {
            return to_route('store_manager.employees.index');
        }

        $employee['image_url'] = $employee->getDiskBasedFirstMediaUrl('photo');

        return Inertia::render('employees/Manage', [
            'employee' => $employee,
            'jobTypes' => JobTypes::formattedForSelection(),
            'designations' => $designationQueries->getByCompanyId(
                session('store_manager_selected_location_company_id')
            ),
            'employeeGroups' => $employeeGroupQueries->getByCompanyId(
                session('store_manager_selected_location_company_id')
            ),
        ]);
    }

    public function update(EmployeeData $employeeData, int $employeeId, Request $request): RedirectResponse
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $this->checkRequestDetails($employeeData);

        $this->checkForOwnRecordUpdate($storeManager, $employeeId);

        DB::beginTransaction();

        try {
            $this->employeeQueries->update($employeeData, $storeManager, $employeeId);

            DB::commit();

            return to_route('store_manager.employees.index')
                ->with('success', 'The employee was updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Employee Store Manager', [
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

    public function exportEmployees(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $employees = $this->employeeQueries->getAdminEmployeesExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new EmployeeExport($employees), $filename);
    }

    private function checkRequestDetails(EmployeeData $employeeData): void
    {
        if (session('store_manager_selected_location_company_id') !== $employeeData->company_id) {
            throw new RedirectWithErrorException(
                'store_manager.employees.index',
                'You do not have access to the company with the ID - ' . $employeeData->company_id
            );
        }
    }

    private function checkForOwnRecordUpdate(StoreManager $storeManager, int $employeeId): void
    {
        if ($storeManager->getEmployeeId() === $employeeId) {
            throw new RedirectWithErrorException(
                'store_manager.employees.index',
                "You cannot edit your own employee record (Sounds fair, doesn't it?)"
            );
        }
    }
}
