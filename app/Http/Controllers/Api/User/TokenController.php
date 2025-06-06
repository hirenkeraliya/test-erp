<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Domains\Common\Enums\LogoutEnums;
use App\Domains\CompanyOwner\DataObjects\CompanyOwnerApplicationLoginData;
use App\Domains\User\Jobs\ForgotPasswordEmailJob;
use App\Domains\User\UserQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function issueToken(CompanyOwnerApplicationLoginData $companyOwnerApplicationLoginData): array
    {
        $userQueries = resolve(UserQueries::class);
        $companyOwner = $userQueries->getCompanyOwnerByUsernameAndPassword($companyOwnerApplicationLoginData);

        if (null === $companyOwner) {
            abort(404, 'Incorrect credentials');
        }

        /** @var Employee $employee */
        $employee = $companyOwner->employee;

        if (! $employee->company || ! $employee->getStatus()) {
            abort(412, 'Your account is inactive. Please contact Admin.');
        }

        $token = $userQueries->createToken($companyOwner);

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

        /** @var User $user */
        $user = $request->user();

        if ((int) $logoutFromAll === LogoutEnums::LOGOUT_FROM_ALL->value) {
            $user->tokens()->delete();
        } else {
            /** @var PersonalAccessToken $currentAccessToken */
            $currentAccessToken = $user->currentAccessToken();
            $tokenId = $currentAccessToken->id;
            $user->revokeCurrentToken($tokenId);
        }

        return [
            'message' => 'Successfully Logged Out!',
        ];
    }

    public function forgotPassword(Request $request): array
    {
        $request->validate([
            'username' => ['required', 'string'],
        ]);

        $userQueries = resolve(UserQueries::class);

        $user = $userQueries->fetchUserByUsername($request->input('username'));

        if ($user instanceof User) {
            if (null === $user->employee?->email) {
                return [
                    'error' => 'Please contact the admin to set up your email address!',
                ];
            }

            /** @var string $forgotPasswordToken */
            $forgotPasswordToken = $user->forgot_password_token;
            ForgotPasswordEmailJob::dispatch($user->id, $user->employee->company_id, $forgotPasswordToken)->onQueue(
                config('horizon.default_queue_name')
            );
        }

        return [
            'message' => 'If an account with the provided email address exists, you will receive an email.',
        ];
    }
}
