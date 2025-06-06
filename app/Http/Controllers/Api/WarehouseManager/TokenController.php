<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Common\Enums\LogoutEnums;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerApplicationLoginData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function issueToken(WarehouseManagerApplicationLoginData $warehouseManagerApplicationLoginData): array
    {
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManager = $warehouseManagerQueries->getWarehouseManagerByUsername(
            $warehouseManagerApplicationLoginData->username
        );

        if (null === $warehouseManager) {
            abort(404, 'Incorrect credentials');
        }

        if (! Auth::guard('warehouse_manager_app')->attempt($warehouseManagerApplicationLoginData->all())) {
            abort(404, 'Username Or Password Incorrect.');
        }

        /** @var Employee $employee */
        $employee = $warehouseManager->employee;

        if (! $employee->company || ! $employee->getStatus()) {
            abort(412, 'Your account is inactive. Please contact Admin/Store Manager.');
        }

        $token = $warehouseManagerQueries->createToken($warehouseManager);

        return [
            'access_token' => $token,
        ];
    }

    public function logout(Request $request): array
    {
        $validatedData = $request->validate([
            'logout_from_all' => ['nullable', 'in:'. LogoutEnums::getValues()],
        ], [
            'logout_from_all.in' => 'The logout from all field must be either 0 or 1.',
        ]);

        $logoutFromAll = $validatedData['logout_from_all'] ?? null;

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        if ((int) $logoutFromAll === LogoutEnums::LOGOUT_FROM_ALL->value) {
            $warehouseManager->tokens()->delete();
        } else {
            /** @var PersonalAccessToken $currentAccessToken */
            $currentAccessToken = $warehouseManager->currentAccessToken();
            $tokenId = $currentAccessToken->id;
            $warehouseManager->revokeCurrentToken($tokenId);
        }

        return [
            'message' => 'Successfully Logged Out!',
        ];
    }

    public function setFcmToken(Request $request): array
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerQueries->updateFcmToken($request->token, $warehouseManager->id, $companyId);

        return [
            'message' => 'Fcm Token Set Successfully',
        ];
    }
}
