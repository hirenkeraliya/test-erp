<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Driver\DataObjects\DriverData;
use App\Domains\Driver\DriverQueries;
use App\Domains\Driver\Resources\DriverResource;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function __construct(
        protected DriverQueries $driverQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('drivers/Index');
    }

    public function fetchDrivers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'per_page' => $request->get('per_page'),
        ];
        $companyId = session('admin_company_id');
        $lengthAwarePaginator = $this->driverQueries->listQuery($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => DriverResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function store(DriverData $driverData): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $companyId = session('admin_company_id');

        $this->driverQueries->addNew($user, $driverData, $companyId);

        return to_route('admin.drivers.index')
            ->with('success', 'Driver added successfully.');
    }

    public function edit(int $driverId): Response
    {
        $companyId = session('admin_company_id');
        $driver = $this->driverQueries->getById($driverId, $companyId);

        return Inertia::render('drivers/Manage', [
            'driver' => new DriverResource($driver),
        ]);
    }

    public function update(DriverData $driverData, int $driverId): RedirectResponse
    {
        $companyId = session('admin_company_id');

        $this->driverQueries->update($driverData, $driverId, $companyId);

        return to_route('admin.drivers.index')
            ->with('success', 'Driver updated successfully.');
    }

    public function changeStatus(int $driverId): void
    {
        $companyId = session('admin_company_id');
        $this->driverQueries->changeStatus($driverId, $companyId);
    }
}
