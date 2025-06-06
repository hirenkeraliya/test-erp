<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Location\LocationQueries;
use App\Domains\Store\DataObjects\StoreSelectionData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getAuthorizedStores(Request $request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locations = $storeManagerQueries->getStoreManagerStores($storeManager);

        return [
            'locations' => $locations,
        ];
    }

    public function storeSelection(Request $request): Response
    {
        return Inertia::render('guest/StoreSelection', [
            'locations' => $this->getAuthorizedStores($request)['locations'],
        ]);
    }

    public function setSelectedStore(StoreSelectionData $storeSelectionData, Request $request): RedirectResponse
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $this->validateStoreId($storeSelectionData->location_id, $storeManager);

        $locationQueries = resolve(LocationQueries::class);
        $companyId = $locationQueries->getCompanyIdOfStore($storeSelectionData->location_id);

        session([
            'store_manager_selected_location_id' => $storeSelectionData->location_id,
            'store_manager_selected_location_company_id' => $companyId,
        ]);

        return redirect()->intended(route('store_manager.dashboard'));
    }

    private function validateStoreId(int $selectedLocationId, StoreManager $storeManager): void
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $locationIds = $storeManagerQueries->getStoreManagerStoresId($storeManager);

        if (! in_array($selectedLocationId, $locationIds, true)) {
            throw new RedirectBackWithErrorException('Selected Store Is Not Valid.');
        }
    }
}
