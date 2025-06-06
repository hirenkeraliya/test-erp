<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\WarehouseManager\DataObjects\WarehouseManagerProfileData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WarehouseManagerProfileController extends Controller
{
    public function editProfile(): Response
    {
        $warehouseManagerId = (int) Auth::id();
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManager = $warehouseManagerQueries->getWarehouseManagerData($warehouseManagerId);

        return Inertia::render('warehouse_manager/Profile', [
            'warehouseManager' => $warehouseManager,
        ]);
    }

    public function updateProfile(
        WarehouseManagerProfileData $warehouseManagerProfileData,
        int $warehouseManagerId
    ): ?RedirectResponse {
        $warehouseManagerData = $warehouseManagerProfileData->all();

        try {
            $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
            $warehouseManagerQueries->updateWarehouseManagerProfile($warehouseManagerId, $warehouseManagerData);

            return to_route('warehouse_manager.dashboard')->with('success', 'Warehouse Manager updated successfully.');
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
