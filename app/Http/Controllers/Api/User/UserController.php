<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Permission\Services\UserPermissionModuleService;
use App\Domains\User\DataObjects\ChangePasswordData;
use App\Domains\User\DataObjects\UserUpdateData;
use App\Domains\User\Resources\UserApplicationProfileDetailsResource;
use App\Domains\User\UserQueries;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private readonly UserQueries $userQueries
    ) {
    }

    public function getProfileDetails(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $warehouseManagerData = $this->userQueries->getByIdWithEmployeeAndMedia($user->id, $companyId);

        return [
            'user_details' => new UserApplicationProfileDetailsResource($warehouseManagerData),
        ];
    }

    public function updateProfile(UserUpdateData $userUpdateData, Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $responseData = [
            'status_code' => null,
            'message' => null,
        ];

        DB::beginTransaction();

        try {
            $employeeQueries = resolve(EmployeeQueries::class);
            $employeeQueries->updateProfile($userUpdateData, $user->employee_id);

            $userQueries = resolve(UserQueries::class);
            $userQueries->updateUsername($user, $userUpdateData->username);

            $responseData['message'] = 'Profile Update Successfully!.';
            $responseData['status_code'] = '200';

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            $responseData['message'] = 'Something Went Wrong.';
            $responseData['status_code'] = $throwable->getCode();

            Log::error('The update of the user`s profile failed due to an error.', [
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

    public function updatePassword(ChangePasswordData $changePasswordData, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (Hash::check($changePasswordData->current_password, $user->password)) {
            $this->userQueries->updatePassword($user, $changePasswordData);

            return response()->json([
                'message' => 'Password updated successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Current password incorrect.',
        ], 400);
    }

    public function listPermission(): array
    {
        return [
            'permissions' => UserPermissionModuleService::preparedPermissionModules(),
        ];
    }
}
