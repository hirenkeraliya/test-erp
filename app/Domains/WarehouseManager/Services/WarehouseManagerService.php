<?php

declare(strict_types=1);

namespace App\Domains\WarehouseManager\Services;

use App\Domains\WarehouseManager\WarehouseManagerQueries;

class WarehouseManagerService
{
    public static function checkAuthorizationForWarehouseManager(int $warehouseManagerId, int $locationId): void
    {
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);
        $warehouseManagerWithWarehouseExists = $warehouseManagerQueries->existsByIdAndWarehouseId(
            $warehouseManagerId,
            $locationId
        );

        if (! $warehouseManagerWithWarehouseExists) {
            abort(412, 'You do not have authorization for the selected warehouse.');
        }
    }
}
