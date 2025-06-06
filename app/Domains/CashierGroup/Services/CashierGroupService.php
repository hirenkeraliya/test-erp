<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup\Services;

use App\Domains\CashierGroup\DataObjects\CashierGroupData;
use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\Common\Enums\PriceOverrideTypes;

class CashierGroupService
{
    public function getCashierGroupData(array $cashierGroupDetails): CashierGroupData
    {
        $permissions = explode(',', $cashierGroupDetails['permissions']);

        $permission_ids = [];
        foreach ($permissions as $permission) {
            if (PermissionTypes::getValueByCaseName(trim($permission)) !== null) {
                $permission_ids[] = PermissionTypes::getValueByCaseName(trim($permission));
            }
        }

        $priceOverrideType = PriceOverrideTypes::getValueByCaseName(trim($cashierGroupDetails['price_override_type']));

        return new CashierGroupData(
            name: (string) $cashierGroupDetails['name'],
            price_override_type: $priceOverrideType,
            price_override_limit_percentage_for_item: (float) $cashierGroupDetails['price_override_limit_percentage_for_item'],
            permission_ids: $permission_ids,
            price_override_limit_percentage_for_cart: (float) $cashierGroupDetails['price_override_limit_percentage_for_cart']
        );
    }
}
