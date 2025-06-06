<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\StoreManager\DataObjects\StoreManagerProfileData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StoreManagerProfileController extends Controller
{
    public function editProfile(): Response
    {
        $storeMangerId = (int) Auth::id();
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getStoreManagerData($storeMangerId);

        return Inertia::render('store_manager/Profile', [
            'storeManager' => $storeManager,
        ]);
    }

    public function updateProfile(
        StoreManagerProfileData $storeManagerProfileData,
        int $storeManagerId
    ): ?RedirectResponse {
        $storeManagerData = $storeManagerProfileData->all();

        try {
            $storeManagerQueries = resolve(StoreManagerQueries::class);
            $storeManagerQueries->updateStoreManagerProfile($storeManagerId, $storeManagerData);

            return to_route('store_manager.dashboard')->with('success', 'Store Manager updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Company', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            return null;
        }
    }
}
