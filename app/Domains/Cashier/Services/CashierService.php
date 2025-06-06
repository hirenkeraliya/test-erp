<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Services;

use App\Domains\Cashier\DataObjects\CashierData;
use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\Location\LocationQueries;

class CashierService
{
    public function getCashierData(array $cashierDetails, int $employeeId, int $companyId): CashierData
    {
        $locationQueries = resolve(LocationQueries::class);
        $cashierGroupQueries = resolve(CashierGroupQueries::class);

        $cashierGroupId = $cashierGroupQueries->getIdByName((string) $cashierDetails['cashier_group']);

        $cashierLocations = explode(',', $cashierDetails['locations']);

        $locations = $locationQueries->getIdAndNameByNames(array_map('trim', $cashierLocations), $companyId);
        $locationIds = $locations->map(fn ($location) => $location->id)->toArray();

        return new CashierData(
            employee_id: $employeeId,
            cashier_group_id: $cashierGroupId,
            username: (string) $cashierDetails['username'],
            pin: (string) $cashierDetails['pin'],
            location_ids: $locationIds
        );
    }
}
