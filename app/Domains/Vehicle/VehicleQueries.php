<?php

declare(strict_types=1);

namespace App\Domains\Vehicle;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Vehicle\DataObjects\VehicleData;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getVehicleQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    private function getVehicleQuery(array $filterData, int $companyId): Builder
    {
        return Vehicle::select('id', 'name', 'plate_no', 'type_of_vehicle', 'status', 'created_at')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $searchText = $filterData['search_text'];
                $query->where(function ($query) use ($searchText): void {
                    $query->where('name', 'like', '%' . $searchText . '%')
                        ->orWhere('plate_no', 'like', '%' . $searchText . '%')
                        ->orWhere('type_of_vehicle', 'like', '%' . $searchText . '%');
                });
            })->orderBy('created_at', 'desc');
    }

    public function addNew(User $user, VehicleData $vehicleData, int $companyId): Vehicle
    {
        return Vehicle::create([
            'company_id' => $companyId,
            'name' => $vehicleData->name,
            'plate_no' => $vehicleData->plate_no,
            'type_of_vehicle' => $vehicleData->type_of_vehicle,
            'status' => $vehicleData->status,
            'created_by_type' => ModelMapping::getCaseName($user::class),
            'created_by_id' => $user->id,
        ]);
    }

    public function getById(int $vehicleId, int $companyId): Vehicle
    {
        return Vehicle::where('company_id', $companyId)->findOrFail($vehicleId);
    }

    public function update(VehicleData $vehicleData, int $vehicleId, int $companyId): Vehicle
    {
        $vehicle = $this->getById($vehicleId, $companyId);

        $vehicle->update([
            'name' => $vehicleData->name,
            'plate_no' => $vehicleData->plate_no,
            'type_of_vehicle' => $vehicleData->type_of_vehicle,
            'status' => $vehicleData->status,
        ]);

        return $vehicle;
    }

    public function changeStatus(int $vehicleId, int $companyId): void
    {
        $vehicle = $this->getById($vehicleId, $companyId);
        $vehicle->status = ! $vehicle->status;
        $vehicle->save();
    }
}
