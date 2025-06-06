<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promoter\Resources\ApplicationProfileDetailsResource;
use App\Domains\StoreManager\DataObjects\StoreManagerApplicationData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreManagerController extends Controller
{
    public function updateProfile(StoreManagerApplicationData $storeManagerApplicationData, Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $responseData = [
            'status_code' => null,
            'message' => null,
        ];

        DB::beginTransaction();

        try {
            $employeeQueries = resolve(EmployeeQueries::class);
            $employeeQueries->updateProfile($storeManagerApplicationData, $storeManager->employee_id);

            $storeManageQueries = resolve(StoreManagerQueries::class);
            $storeManageQueries->updateUsername($storeManager, $storeManagerApplicationData->username);

            $responseData['message'] = 'Profile Update Successfully!.';
            $responseData['status_code'] = '200';

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            $responseData['message'] = 'Something Went Wrong.';
            $responseData['status_code'] = $throwable->getCode();

            Log::error('The update of the store manager`s profile failed due to an error.', [
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
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerData = $storeManagerQueries->getByIdWithStores($storeManager->id, $companyId);

        return [
            'store_manager_details' => new ApplicationProfileDetailsResource($storeManagerData),
        ];
    }
}
