<?php

declare(strict_types=1);

namespace App\Domains\SuperAdmin;

use App\Domains\SuperAdmin\DataObjects\ChangePasswordData;
use App\Domains\SuperAdmin\DataObjects\SuperAdminChangePasswordData;
use App\Domains\SuperAdmin\DataObjects\SuperAdminData;
use App\Models\SuperAdmin;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SuperAdminQueries
{
    public function changePassword(SuperAdmin $superAdmin, ChangePasswordData $changePasswordData): void
    {
        $superAdmin->password = bcrypt($changePasswordData->new_password);
        $superAdmin->save();
    }

    public function getByUsername(string $username): ?SuperAdmin
    {
        return SuperAdmin::select('id', 'username', 'password')
            ->whereCaseSensitive('username', $username)
            ->first();
    }

    public function fetchSuperAdminByEmail(string $email): ?SuperAdmin
    {
        return SuperAdmin::select('id', 'email')->where('email', $email)
            ->first();
    }

    public function getAll(): Collection
    {
        return SuperAdmin::select('id', 'name', 'email')->get();
    }

    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return SuperAdmin::query()
            ->select('id', 'username', 'email', 'name', 'is_email_verified')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('username', 'like', '%' . $filterData['search_text'] . '%');
                    $query->orWhere('name', 'like', '%' . $filterData['search_text'] . '%');
                    $query->orWhere('email', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(SuperAdminData $superAdminData): void
    {
        $superAdminData->password = bcrypt((string) $superAdminData->password);
        $data = $superAdminData->all();
        SuperAdmin::create($data);
    }

    public function getById(int $superAdminId): SuperAdmin
    {
        return SuperAdmin::select(
            'id',
            'username',
            'name',
            'email',
            'two_factor_secret',
            'is_email_verified'
        )->findOrFail($superAdminId);
    }

    public function update(SuperAdminData $superAdminData, SuperAdmin $superAdmin): void
    {
        $superAdminDetails = $superAdminData->toArray();
        unset($superAdminDetails['password']);

        $superAdmin->update($superAdminDetails);
    }

    public function superAdminChangePassword(
        SuperAdmin $superAdmin,
        SuperAdminChangePasswordData $superAdminChangePasswordData
    ): void {
        $superAdmin->password = bcrypt($superAdminChangePasswordData->new_password);
        $superAdmin->save();
    }

    public function getByIdForEmailVerification(int $superAdminId): SuperAdmin
    {
        return SuperAdmin::select('id', 'email')
            ->findOrFail($superAdminId);
    }
}
