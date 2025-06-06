<?php

declare(strict_types=1);

namespace App\Domains\User;

use App\Domains\Company\CompanyQueries;
use App\Domains\CompanyOwner\DataObjects\CompanyOwnerApplicationLoginData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\User\DataObjects\ChangePasswordData;
use App\Domains\User\DataObjects\UserChangePasswordData;
use App\Domains\User\DataObjects\UserData;
use App\Domains\User\Enums\UserTypes;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getUsers($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getUsersExport(array $filterData, int $companyId): Collection
    {
        return $this->getUsers($filterData, $companyId)->get();
    }

    private function getUsers(array $filterData, int $companyId): Builder
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return User::query()
            ->select('id', 'username', 'employee_id', 'type_id')
            ->with(['employee:' . $employeeQueries->getBasicColumnNames()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('username', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['employee_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('employee_id', (array) $filterData['employee_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function addNew(UserData $userData): void
    {
        $userData->password = bcrypt((string) $userData->password);
        $data = $userData->all();
        User::create($data);
    }

    public function getById(int $userId, int $companyId): User
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return User::select('id', 'username', 'type_id', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($userId);
    }

    public function update(UserData $userData, User $user): void
    {
        $userDetails = $userData->toArray();
        unset($userDetails['password']);

        $user->update($userDetails);
    }

    public function userChangePassword(User $user, UserChangePasswordData $userChangePasswordData): void
    {
        $user->password = bcrypt($userChangePasswordData->new_password);
        $user->save();
    }

    public function updateUsername(User $user, string $username): void
    {
        $user->username = $username;
        $user->save();
    }

    public function getCompanyOwnerByUsernameAndPassword(
        CompanyOwnerApplicationLoginData $companyOwnerApplicationLoginData
    ): ?User {
        $employeeQueries = resolve(EmployeeQueries::class);
        $companyQueries = new CompanyQueries();

        $user = User::select('id', 'employee_id', 'password')
            ->with('employee:' . $employeeQueries->getBasicColumnNamesWithStatus())
            ->with(['employee.company:' . $companyQueries->getBasicColumnNames()])
            ->where('username', $companyOwnerApplicationLoginData->username)
            ->where('type_id', UserTypes::COMPANY_OWNER->value)
            ->first();

        if ($user && Hash::check($companyOwnerApplicationLoginData->password, $user->password)) {
            return $user;
        }

        return null;
    }

    public function createToken(User $user): string
    {
        return $user->createToken('company_owner_app', ['company_owner_scope'])->plainTextToken;
    }

    public function updatePassword(User $user, ChangePasswordData $changePasswordData): void
    {
        $user->password = bcrypt($changePasswordData->new_password);
        $user->save();
    }

    public function fetchUserByUsername(string $username): ?User
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        $user = User::select(
            'id',
            'username',
            'employee_id',
            'forgot_password_token',
            'forgot_password_token_expiration_at'
        )
            ->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->whereCaseSensitive('username', $username)
            ->first();

        if (null !== $user) {
            /** @var Employee $employee */
            $employee = $user->employee;
            $user->forgot_password_token = md5($employee->email . now()->format('Y-m-d H:i:s'));
            $user->forgot_password_token_expiration_at = now()->addHour()->format('Y-m-d H:i:s');
            $user->save();
        }

        return $user;
    }

    public function checkResetPasswordToken(string $token): User
    {
        return User::where('forgot_password_token', $token)
            ->where('forgot_password_token_expiration_at', '>=', now())
            ->firstOrFail();
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->password = bcrypt($password);
        $user->forgot_password_token = null;
        $user->forgot_password_token_expiration_at = null;
        $user->save();
    }

    public function getByIdWithEmployeeAndMedia(int $storeManagerId, int $companyId): User
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $mediaQueries = resolve(MediaQueries::class);

        return User::select('id', 'username', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->with(
                'employee:' . $employeeQueries->getColumnNamesForPromoter(),
                'employee.media:' . $mediaQueries->getBasicColumnNames(),
            )
            ->findOrFail($storeManagerId);
    }
}
