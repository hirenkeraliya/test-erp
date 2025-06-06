<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\Domains\Location\LocationQueries;
use App\Domains\StoreManager\Services\StoreManagerService;
use App\Domains\Vendor\DataObjects\VendorListForStoreManagerAppData;
use App\Domains\Vendor\Resources\VendorListApiResource;
use App\Domains\Vendor\VendorQueries;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function getVendors(
        Request $request,
        VendorListForStoreManagerAppData $vendorListForStoreManagerAppData
    ): array {
        $searchText = $vendorListForStoreManagerAppData->search_text ?? null;

        $vendorQueries = resolve(VendorQueries::class);

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerService = resolve(StoreManagerService::class);

        /** @var int $locationId */
        $locationId = $vendorListForStoreManagerAppData->store_id ?? $vendorListForStoreManagerAppData->location_id;

        $storeManagerService->checkAuthorizationForStoreManager($storeManager->id, $locationId);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($locationId);

        $vendors = $vendorQueries->getVendorByCompanyId($companyId, $searchText);

        return [
            'vendors' => VendorListApiResource::collection($vendors),
        ];
    }
}
