<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\Member\Services\MemberService;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeQueries $employeeQueries
    ) {
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchEmployees(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->employeeQueries->superAdminListQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    /**
     * @return mixed[]
     */
    public function getByCompanyId(int $companyId): array
    {
        return [
            'data' => $this->employeeQueries->getFormattedEmployeesOf($companyId),
        ];
    }

    public function create(): Response
    {
        $companyQueries = new CompanyQueries();

        return Inertia::render('employees/Manage', [
            'jobTypes' => JobTypes::formattedForSelection(),
            'companies' => $companyQueries->getWithBasicColumns(),
        ]);
    }

    public function store(EmployeeData $employeeData, Request $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = $request->user();

            $employee = $this->employeeQueries->addNew($employeeData, $user);

            $memberService = resolve(MemberService::class);
            $memberService->addNewEmployeeMember($employee);

            DB::commit();

            return to_route('super_admin.employees.index')
                ->with('success', 'Employee added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Employee Super Admin', [
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
        /** @var SuperAdmin $superAdmin */
        $superAdmin = $request->user();

        $this->employeeQueries->superAdminSetStatus($employeeId, $status, $superAdmin);

        return to_route('super_admin.employees.index')->with('success', 'Status changed successfully.');
    }

    public function edit(int $employeeId, CompanyQueries $companyQueries): Response
    {
        $employee = $this->employeeQueries->getByIdWithMedia($employeeId);
        $employee['image_url'] = $employee->getDiskBasedFirstMediaUrl('photo');

        return Inertia::render('employees/Manage', [
            'employee' => $employee,
            'jobTypes' => JobTypes::formattedForSelection(),
            'companies' => $companyQueries->getWithBasicColumns(),
        ]);
    }

    public function update(EmployeeData $employeeData, int $employeeId, Request $request): RedirectResponse
    {
        /** @var SuperAdmin $superAdmin */
        $superAdmin = $request->user();

        DB::beginTransaction();

        try {
            $this->employeeQueries->update($employeeData, $superAdmin, $employeeId);

            DB::commit();

            return to_route('super_admin.employees.index')
                ->with('success', 'The employee was updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Employee Super Admin', [
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
}
