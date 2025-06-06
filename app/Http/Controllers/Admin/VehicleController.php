<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Vehicle\DataObjects\VehicleData;
use App\Domains\Vehicle\Resources\VehicleResource;
use App\Domains\Vehicle\VehicleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(
        protected VehicleQueries $vehicleQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('vehicles/Index');
    }

    public function fetchVehicles(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'per_page' => $request->get('per_page'),
        ];
        $companyId = session('admin_company_id');
        $lengthAwarePaginator = $this->vehicleQueries->listQuery($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => VehicleResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function store(VehicleData $vehicleData): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $companyId = session('admin_company_id');

        $this->vehicleQueries->addNew($user, $vehicleData, $companyId);

        return to_route('admin.vehicles.index')
            ->with('success', 'Vehicle added successfully.');
    }

    public function edit(int $vehicleId): Response
    {
        $companyId = session('admin_company_id');
        $vehicle = $this->vehicleQueries->getById($vehicleId, $companyId);

        return Inertia::render('vehicles/Manage', [
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    public function update(VehicleData $vehicleData, int $vehicleId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $this->vehicleQueries->update($vehicleData, $vehicleId, $companyId);

        return to_route('admin.vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function changeStatus(int $vehicleId): void
    {
        $companyId = session('admin_company_id');
        $this->vehicleQueries->changeStatus($vehicleId, $companyId);
    }
}
