<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function getProductsClosingStocksPerDay(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $dateRange = $request->date_range ?? null;

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdates = $inventoryUpdateQueries->getAllByCompanyId($companyId, $dateRange);

        $inventoryUpdates->transform(function ($inventoryUpdate) use ($companyId) {
            $inventoryUpdate->company_id = $companyId;

            return $inventoryUpdate;
        });

        return [
            'inventory_updates' => $inventoryUpdates,
        ];
    }

    public function getProductsCurrentStock(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventories = $inventoryQueries->getAllByCompanyId($companyId);

        $inventories->transform(function ($inventory) use ($companyId) {
            $inventory->company_id = $companyId;

            return $inventory;
        });

        return [
            'inventories' => $inventories,
        ];
    }
}
