<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Common\Enums\LogoutEnums;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\StoreManager\DataObjects\StoreManagerApplicationLoginData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function issueToken(StoreManagerApplicationLoginData $storeManagerApplicationLoginData): array
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getStoreManagerByUsername($storeManagerApplicationLoginData->username);

        if (null === $storeManager) {
            abort(404, 'Incorrect credentials');
        }

        if (! Auth::guard('store_manager_app')->attempt($storeManagerApplicationLoginData->all())) {
            abort(404, 'Username Or Password Incorrect.');
        }

        /** @var Employee $employee */
        $employee = $storeManager->employee;

        if (! $employee->company || ! $employee->getStatus()) {
            abort(412, 'Your account is inactive. Please contact Admin/Store Manager.');
        }

        $token = $storeManagerQueries->createToken($storeManager);

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

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        if ((int) $logoutFromAll === LogoutEnums::LOGOUT_FROM_ALL->value) {
            $storeManager->tokens()->delete();
        } else {
            /** @var PersonalAccessToken $currentAccessToken */
            $currentAccessToken = $storeManager->currentAccessToken();
            $tokenId = $currentAccessToken->id;
            $storeManager->revokeCurrentToken($tokenId);
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

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerQueries->updateFcmToken($request->token, $storeManager->id, $companyId);

        return [
            'message' => 'Fcm Token Set Successfully',
        ];
    }
}
