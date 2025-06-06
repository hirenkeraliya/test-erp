<?php

declare(strict_types=1);

namespace App\Domains\Permission\Services;

use App\CommonFunctions;
use App\Domains\Permission\Enums\PermissionList;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WarehouseManagerPermissionModuleService
{
    public static function preparedPermissionModules(): Collection
    {
        $defaultPermissions = collect([]);

        foreach (self::getModuleSubLists() as $key => $permissionModule) {
            $defaultPermissions->push([
                'id' => Str::lower($key),
                'name' => CommonFunctions::stringTitleLowerCase($key),
                'action' => false,
                'children' => collect([]),
            ]);

            foreach ($permissionModule as $moduleName) {
                $defaultPermission = $defaultPermissions->firstWhere(
                    'name',
                    CommonFunctions::stringTitleLowerCase($key)
                );

                $defaultPermission['children']->push([
                    'id' => Str::lower($key) . '_' . $moduleName,
                    'name' => CommonFunctions::stringTitleLowerCase($moduleName),
                    'action' => false,
                ]);
            }
        }

        return $defaultPermissions;
    }

    public static function preparedEditRecord(Role $role): Collection
    {
        $rolePermissions = $role->permissions;

        return static::preparedPermissionModules()->transform(function (array $permission) use (
            $rolePermissions
        ): array {
            $permission['children']->transform(function (array $childPermission) use ($rolePermissions): array {
                $rolePermission = $rolePermissions->firstWhere('name', $childPermission['id']);

                return [
                    'id' => $childPermission['id'],
                    'name' => $childPermission['name'],
                    'action' => (bool) $rolePermission,
                ];
            });
            if ($permission['children']->every('action', true)) {
                $permission['action'] = true;
            }

            return $permission;
        });
    }

    public static function getModuleSubLists(): array
    {
        return [
            'Dashboard' => [PermissionList::DASHBOARD_STOCK_OVERVIEW->value],
            'Product' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Barcode' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Goods_Received_Note' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Stock_Adjustment' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Stock_Transfer' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Stock_Transfer_Overview' => [PermissionList::READ_RECORD->value],
            'Stock_Take' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Purchase_Order' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Purchase_Order_Invoice' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Import_Record' => [PermissionList::READ_RECORD->value, PermissionList::WRITE_RECORD->value],
            'Export_Record' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Reserved_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Transit_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Stock_Movement_Ledger' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Batch_Expiry' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'External_Login' => [PermissionList::READ_RECORD->value],
            'Custom_Report' => [PermissionList::READ_RECORD->value],
            'External_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
        ];
    }
}
