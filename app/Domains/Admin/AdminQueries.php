<?php

declare(strict_types=1);

namespace App\Domains\Admin;

use App\Domains\Admin\DataObjects\AdminChangePasswordData;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Admin\DataObjects\ChangePasswordData;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Role\RoleQueries;
use App\Models\Admin;
use App\Models\Employee;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        $employeeQueries = new EmployeeQueries();
        $companyQueries = new CompanyQueries();

        return Admin::query()
            ->with(
                [
                    'employee:' . $employeeQueries->getBasicColumnNames(),
                    'employee.company:' . $companyQueries->getBasicColumnNames(),
                ]
            )
            ->whereHas('employee.company', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->select('id', 'username', 'employee_id')
            ->when($filterData['search_text'], function ($query) use (
                $companyQueries,
                $employeeQueries,
                $filterData
            ): void {
                $query->where(function ($query) use ($companyQueries, $employeeQueries, $filterData): void {
                    $query->where('username', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('employee', $employeeQueries->searchByBasicColumns($filterData['search_text']))
                        ->orWhereHas('employee', function ($query) use ($companyQueries, $filterData): void {
                            $query->select('id', 'company_id')
                                ->whereHas('company', $companyQueries->searchByName($filterData['search_text']));
                        });
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function changePassword(Admin $admin, ChangePasswordData $changePasswordData): void
    {
        $admin->password = bcrypt($changePasswordData->new_password);
        $admin->save();
    }

    public static function getEmployeeFullName(Admin $admin): string
    {
        $employeeQueries = new EmployeeQueries();
        $admin->load('employee:' . $employeeQueries->getFirstAndLastNameColumns());

        /** @var Employee $employee */
        $employee = $admin->employee;

        return $employee->getFullName();
    }

    public function addNew(AdminData $adminData): void
    {
        $adminData->password = bcrypt((string) $adminData->password);
        $roleIds = $adminData->role_ids;
        $data = $adminData->all();

        unset($data['role_ids']);

        $admin = Admin::create($data);

        $admin->ulid = (string) Str::ulid();
        $admin->save();

        $admin->syncRoles($roleIds);
    }

    public function getByIdWithEmployee(int $adminId): Admin
    {
        $employeeQueries = resolve(EmployeeQueries::class);
        $roleQueries = resolve(RoleQueries::class);

        return Admin::select('id', 'username', 'employee_id')
            ->with([
                'employee:' . $employeeQueries->getColumnNamesForAdmin(),
                'roles:' . $roleQueries->getBasicColumns(),
            ])
            ->findOrFail($adminId);
    }

    public function update(AdminData $adminData, int $adminId): void
    {
        $admin = Admin::findOrFail($adminId);
        $adminDetails = $adminData->toArray();

        unset($adminDetails['password']);
        unset($adminDetails['role_ids']);
        $admin->update($adminDetails);

        $admin->syncRoles($adminData->role_ids);
    }

    public function getById(int $adminId): Admin
    {
        return Admin::select('id')->findOrFail($adminId);
    }

    public function fetchAdminByUsername(string $username): ?Admin
    {
        $employeeQueries = new EmployeeQueries();

        $admin = Admin::select(
            'id',
            'username',
            'employee_id',
            'forgot_password_token',
            'forgot_password_token_expiration_at'
        )->with('employee:' . $employeeQueries->getBasicColumnNames())
            ->whereCaseSensitive('username', $username)
            ->first();

        if (null !== $admin) {
            /** @var Employee $employee */
            $employee = $admin->employee;
            $admin->forgot_password_token = md5($employee->email . now()->format('Y-m-d H:i:s'));
            $admin->forgot_password_token_expiration_at = now()->addHour()->format('Y-m-d H:i:s');
            $admin->save();
        }

        return $admin;
    }

    public function checkResetPasswordToken(string $token): Admin
    {
        return Admin::where('forgot_password_token', $token)
            ->where('forgot_password_token_expiration_at', '>=', now())
            ->firstOrFail();
    }

    public function resetPassword(Admin $admin, string $password): void
    {
        $admin->password = bcrypt($password);
        $admin->forgot_password_token = null;
        $admin->forgot_password_token_expiration_at = null;
        $admin->save();
    }

    public function getEmployeeWithRelation(): Closure
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return fn ($query) => $query->select('id', 'employee_id')
            ->with('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function getAdminListByCompanyId(int $companyId): Collection
    {
        $employeeQueries = new EmployeeQueries();

        return Admin::query()
            ->select('id', 'employee_id')
            ->with(['employee:' . $employeeQueries->getBasicColumnNames()])
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->get();
    }

    public function loadEmployee(Admin $admin): Admin
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return $admin->load('employee:' . $employeeQueries->getNameAndStaffIdColumns());
    }

    public function adminChangePassword(Admin $admin, AdminChangePasswordData $changePasswordData): void
    {
        $admin->password = bcrypt($changePasswordData->new_password);
        $admin->save();
    }

    public function getByAdminCompanyId(int $adminId, int $companyId): Admin
    {
        $employeeQueries = new EmployeeQueries();

        return Admin::query()
            ->select('id', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->findOrFail($adminId);
    }

    public function updateExternalLoginToken(int $adminId, int $companyId, string $token): void
    {
        $admin = $this->getByAdminCompanyId($adminId, $companyId);
        $admin->external_login_token = $token;
        $admin->save();
    }

    public function getByStaffIdAndCompanyId(string $staffId, int $companyId): ?Admin
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Admin::select('id', 'username', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompanyAndStaffId($staffId, $companyId))
            ->first();
    }

    public function getByIdAndExternalLoginToken(int $id, string $token): Admin
    {
        $employeeQueries = resolve(EmployeeQueries::class);

        return Admin::select('id', 'username', 'employee_id')
            ->with(['employee:' . $employeeQueries->getNameAndStaffIdColumns()])
            ->where('id', $id)
            ->where('external_login_token', $token)
            ->firstOrFail();
    }

    public function getByCompanyIdOnlyId(int $companyId): Collection
    {
        $employeeQueries = new EmployeeQueries();

        return Admin::query()
            ->select('id', 'employee_id')
            ->whereHas('employee', $employeeQueries->filterByCompany($companyId))
            ->get();
    }

    public function getAdminData(int $id): Admin
    {
        return Admin::select('id', 'username', 'employee_id', 'two_factor_secret')->findOrFail($id);
    }
}
