<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\Domains\Common\Enums\LogoutEnums;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promoter\DataObjects\PromoterApplicationLoginData;
use App\Domains\Promoter\PromoterQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function issueToken(PromoterApplicationLoginData $promoterApplicationLoginData): array
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $promoter = $promoterQueries->getPromoterByUsername($promoterApplicationLoginData->username);

        if (null === $promoter) {
            abort(404, 'Incorrect credentials');
        }

        if (! Auth::guard('promoter_app')->attempt($promoterApplicationLoginData->all())) {
            abort(404, 'Username Or Password Incorrect.');
        }

        /** @var Employee $employee */
        $employee = $promoter->employee;
        if (! $employee->company || ! $employee->getStatus()) {
            abort(412, 'Your account is inactive. Please contact Admin/Store Manager.');
        }

        $token = $promoterQueries->createToken($promoter);

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

        /** @var Promoter $promoter */
        $promoter = $request->user();

        if ((int) $logoutFromAll === LogoutEnums::LOGOUT_FROM_ALL->value) {
            $promoter->tokens()->delete();
        } else {
            /** @var PersonalAccessToken $currentAccessToken */
            $currentAccessToken = $promoter->currentAccessToken();
            $tokenId = $currentAccessToken->id;
            $promoter->revokeCurrentToken($tokenId);
        }

        return [
            'message' => 'Successfully Logged Out!',
        ];
    }

    public function setFcmToken(Request $request): array
    {
        $promoterQueries = resolve(PromoterQueries::class);

        $request->validate([
            'token' => ['required', 'string'],
        ]);

        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        $promoterQueries->updateFcmToken($request->token, $promoter->id, $companyId);

        return [
            'message' => 'Fcm Token Set Successfully',
        ];
    }
}
