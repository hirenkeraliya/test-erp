<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\DataObjects\StoreManagerApiEmployeeData;
use App\Domains\Employee\DataObjects\StoreManagerEmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\Employee\Resources\StoreManagerAppEmployeeResource;
use App\Domains\Employee\Resources\StoreManagerEmployeeResource;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Member\Services\MemberService;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedList(Request $request, StoreManagerApiEmployeeData $storeManagerApiEmployeeData): array
    {
        $filteredData = [
            'per_page' => $storeManagerApiEmployeeData->per_page,
            'sort_by' => $storeManagerApiEmployeeData->sort_by,
            'search_text' => $storeManagerApiEmployeeData->search_text,
            'sort_direction' => $storeManagerApiEmployeeData->sort_direction,
        ];

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $employees = $employeeQueries->getPaginatedListForStoreManagerApp($filteredData, $companyId);

        return [
            'employees' => StoreManagerAppEmployeeResource::collection($employees),
            'total_records' => $employees->total(),
            'last_page' => $employees->lastPage(),
            'current_page' => $employees->currentPage(),
            'per_page' => $employees->perPage(),
        ];
    }

    public function store(StoreManagerEmployeeData $storeManagerEmployeeData, Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $employee = $employeeQueries->addNewForStoreManagerApp($storeManagerEmployeeData, $storeManager, $companyId);

        $memberService = resolve(MemberService::class);
        $memberService->addNewEmployeeMember($employee);

        return [
            'employee' => new StoreManagerEmployeeResource($employee),
        ];
    }

    public function getEmployeeDetails(int $employeeId): array
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $employee = $employeeQueries->getByIdWithMedia($employeeId);

        return [
            'employee' => new StoreManagerEmployeeResource($employee),
        ];
    }

    public function update(int $employeeId, StoreManagerEmployeeData $storeManagerEmployeeData, Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        $employeeQueries->update($storeManagerEmployeeData, $storeManager, $employeeId);

        $employee = $employeeQueries->getByIdWithMedia($employeeId);

        return [
            'employee' => new StoreManagerEmployeeResource($employee),
        ];
    }

    public function getEmployeeGroupList(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $employeeGroupQueries = resolve(EmployeeGroupQueries::class);

        return [
            'employeeGroups' => $employeeGroupQueries->getByCompanyId($companyId),
        ];
    }

    public function getJobTypeList(): array
    {
        return [
            'jobTypes' => JobTypes::formattedForSelection(),
        ];
    }

    public function getDesignationList(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $designationQueries = resolve(DesignationQueries::class);

        return [
            'designations' => $designationQueries->getByCompanyId($companyId),
        ];
    }

    public function emailVerification(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $employee = $employeeQueries->getById($storeManager->employee_id, $companyId);

        if (null === $employee->email) {
            abort(412, 'The email is not set.');
        }

        if ($employee->is_email_verified) {
            abort(412, 'The email is already verified.');
        }

        EmailVerificationJob::dispatch($employee)->delay(now()->addSeconds(5))->onQueue('high');

        return [
            'message' => 'The verification mail sent successfully.',
        ];
    }
}
