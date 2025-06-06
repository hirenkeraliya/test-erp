<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\Domains\Location\LocationQueries;
use App\Domains\Vendor\DataObjects\VendorListForWarehouseManagerAppData;
use App\Domains\Vendor\Resources\VendorListApiResource;
use App\Domains\Vendor\VendorQueries;
use App\Domains\WarehouseManager\Services\WarehouseManagerService;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getVendors(
        Request $request,
        VendorListForWarehouseManagerAppData $vendorListForWarehouseManagerAppData
    ): array {
        $vendorQueries = resolve(VendorQueries::class);

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $warehouseManagerService = resolve(WarehouseManagerService::class);

        /** @var int $locationId */
        $locationId = $vendorListForWarehouseManagerAppData->warehouse_id ??
            $vendorListForWarehouseManagerAppData->location_id;

        $warehouseManagerService->checkAuthorizationForWarehouseManager($warehouseManager->id, (int) $locationId);

        $searchText = $vendorListForWarehouseManagerAppData->search_text ?? null;

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfWarehouse((int) $locationId);

        $vendors = $vendorQueries->getVendorByCompanyId($companyId, $searchText);

        return [
            'vendors' => VendorListApiResource::collection($vendors),
        ];
    }
}
