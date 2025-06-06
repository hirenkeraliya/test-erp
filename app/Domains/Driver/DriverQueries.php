<?php

declare(strict_types=1);

namespace App\Domains\Driver;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Driver\DataObjects\DriverData;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DriverQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getDriverQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    private function getDriverQuery(array $filterData, int $companyId): Builder
    {
        return Driver::select(
            'id',
            'name',
            'id_number',
            'email',
            'mobile_number',
            'country_code',
            'status',
            'created_at'
        )
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $searchText = $filterData['search_text'];
                $query->where(function ($query) use ($searchText): void {
                    $query->where('name', 'like', '%' . $searchText . '%')
                        ->orWhere('id_number', 'like', '%' . $searchText . '%')
                        ->orWhere('email', 'like', '%' . $searchText . '%')
                        ->orWhere('mobile_number', 'like', '%' . $searchText . '%');
                });
            })->orderBy('created_at', 'desc');
    }

    public function addNew(User $user, DriverData $driverData, int $companyId): Driver
    {
        return Driver::create([
            'company_id' => $companyId,
            'name' => $driverData->name,
            'id_number' => $driverData->id_number,
            'email' => $driverData->email,
            'mobile_number' => $driverData->mobile_number,
            'country_code' => $driverData->country_code,
            'status' => $driverData->status,
            'created_by_type' => ModelMapping::getCaseName($user::class),
            'created_by_id' => $user->id,
        ]);
    }

    public function getById(int $driverId, int $companyId): Driver
    {
        return Driver::where('company_id', $companyId)->findOrFail($driverId);
    }

    public function update(DriverData $driverData, int $driverId, int $companyId): Driver
    {
        $driver = $this->getById($driverId, $companyId);

        $driver->update([
            'name' => $driverData->name,
            'id_number' => $driverData->id_number,
            'email' => $driverData->email,
            'mobile_number' => $driverData->mobile_number,
            'country_code' => $driverData->country_code,
            'status' => $driverData->status,
        ]);

        return $driver;
    }

    public function changeStatus(int $driverId, int $companyId): void
    {
        $driver = $this->getById($driverId, $companyId);
        $driver->status = ! $driver->status;
        $driver->save();
    }
}
