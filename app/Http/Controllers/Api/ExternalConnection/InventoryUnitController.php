<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryUnitController extends Controller
{
    public function getBatchInventoryUnits(Request $request): array
    {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $batchInventory = $inventoryUnitQueries->getInventoryUnitsByBatchAndUpc(
            $request->get('batch_details'),
            $request->get('external_location_id'),
            $request->get('upc')
        );

        return [
            'batch_inventory_units' => $batchInventory,
        ];
    }
}
