<?php

declare(strict_types=1);

namespace App\Domains\Permission\Services;

use App\CommonFunctions;
use App\Domains\Permission\Enums\PermissionList;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StoreManagerPermissionModuleService
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
            'Dashboard' => [
                PermissionList::DASHBOARD_OPERATIONAL->value,
                PermissionList::DASHBOARD_STORE_REVENUE->value,
                PermissionList::DASHBOARD_STOCK_OVERVIEW->value,
            ],
            'Product' => [
                PermissionList::READ_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
                PermissionList::PRODUCT_UPLOAD_IMAGE->value,
            ],
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
            'Employee' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Employee_Group' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Designation' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Promoter' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Promoter_Group' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Director' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Cashier' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Cashier_Group' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Sale_Target' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Day_Close' => [
                PermissionList::READ_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
                PermissionList::WRITE_RECORD->value,
            ],
            'Import_Record' => [PermissionList::READ_RECORD->value, PermissionList::WRITE_RECORD->value],
            'Export_Record' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'App_Releases' => [PermissionList::READ_RECORD->value],
            'Custom_Report' => [PermissionList::READ_RECORD->value],
            'Member' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Order' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Order_return' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale_Return' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Different_Store_Return' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Layaway_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Cancel_Layaway_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Credit_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Void_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sales_By_Promoter' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale_Exchange' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Shift_Close' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Day_Close_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Payment_Type_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Cash_Movement' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale_Achieved_Target' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Commission' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Member_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Employee_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Reserved_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Transit_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Product_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Stock_Movement_Ledger' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Product_Ageing' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Voucher' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Booking_Payment' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Member_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Credit_Note' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Batch_Expiry' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'External_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Order_Picking_Lists' => [PermissionList::READ_RECORD->value],
            'Online_Product_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
        ];
    }
}
