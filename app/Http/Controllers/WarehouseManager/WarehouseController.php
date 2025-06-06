<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Location\LocationQueries;
use App\Domains\Warehouse\DataObjects\WarehouseSelectionData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getAuthorizedWarehouses(Request $request): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $locations = $warehouseManagerQueries->getwarehouseManagerWarehouses($warehouseManager);

        return [
            'locations' => $locations,
        ];
    }

    public function warehouseSelection(Request $request): Response
    {
        return Inertia::render('guest/WarehouseSelection', [
            'locations' => $this->getAuthorizedWarehouses($request)['locations'],
        ]);
    }

    public function setSelectedWarehouse(
        WarehouseSelectionData $warehousesSelectionData,
        Request $request
    ): RedirectResponse {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $this->validateWarehouseId($warehousesSelectionData->location_id, $warehouseManager);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse($warehousesSelectionData->location_id);

        session([
            'warehouse_manager_selected_location_id' => $warehousesSelectionData->location_id,
            'warehouse_manager_selected_location_company_id' => $companyId,
        ]);

        return redirect()->intended(route('warehouse_manager.dashboard'));
    }

    private function validateWarehouseId(int $selectedWarehouseId, WarehouseManager $warehouseManager): void
    {
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $locationIds = $warehouseManagerQueries->getWarehouseManagerWarehouseIds($warehouseManager);

        if (! in_array($selectedWarehouseId, $locationIds, true)) {
            throw new RedirectBackWithErrorException('Selected Warehouse Is Not Valid.');
        }
    }
}
