<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Employee\DataObjects\WarehouseManagerApplicationData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promoter\Resources\ApplicationProfileDetailsResource;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WarehouseManagerController extends Controller
{
    public function updateProfile(WarehouseManagerApplicationData $employeeData, Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $responseData = [
            'status_code' => null,
            'message' => null,
        ];

        DB::beginTransaction();

        try {
            $employeeQueries = resolve(EmployeeQueries::class);
            $employeeQueries->updateProfile($employeeData, $warehouseManager->employee_id);

            $employeeQueries = resolve(WarehouseManagerQueries::class);
            $employeeQueries->updateUsername($warehouseManager, $employeeData->username);

            $responseData['message'] = 'Profile Update Successfully!';
            $responseData['status_code'] = '200';

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            $responseData['message'] = 'Something Went Wrong.';
            $responseData['status_code'] = $throwable->getCode();

            Log::error('Error: Update profile failed in the Warehouse Manager application', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        return $responseData;
    }

    public function getProfileDetails(Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerData = $warehouseManagerQueries->getByIdWithWarehouses($warehouseManager->id, $companyId);

        return [
            'warehouse_manager_details' => new ApplicationProfileDetailsResource($warehouseManagerData),
        ];
    }

    public function emailVerification(Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $employee = $employeeQueries->getById($warehouseManager->employee_id, $companyId);

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
