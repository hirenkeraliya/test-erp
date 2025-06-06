<?php

declare(strict_types=1);

namespace App\Domains\Permission\Services;

use App\CommonFunctions;
use App\Domains\Permission\Enums\PermissionList;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionModuleService
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
                PermissionList::DASHBOARD_BUSINESS->value,
                PermissionList::DASHBOARD_STOCK_OVERVIEW->value,
                PermissionList::DASHBOARD_SALE_TARGET->value,
                PermissionList::DASHBOARD_SEASONAL->value,
                PermissionList::DASHBOARD_MEMBER->value,
            ],
            'Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale_Return' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Different_Store_Return' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Layaway_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Cancel_Layaway_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Credit_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Void_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sales_By_Promoter' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale_Exchange' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Pos_Advertisement' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'App_Release' => [PermissionList::READ_RECORD->value],
            'Employee' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],

            'Unit_Of_Measure' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],

            'Unit_Of_Measure_Derivative' => [
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
            'Employee_Group' => [
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
            'Counter' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Vendor' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Region' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Email_Recipient' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Denomination' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Import_Record' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Export_Record' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Automated_Notification' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Manual_Notification' => [PermissionList::READ_RECORD->value, PermissionList::WRITE_RECORD->value],
            'Store_Manager' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Warehouse_Manager' => [
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
            'Driver' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Vehicle' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
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
            'Package_Type' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Style' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Season' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Product' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
                PermissionList::PRODUCT_PURCHASE_COST->value,
                PermissionList::EXPORT_RECORD->value,
                PermissionList::PRODUCT_UPLOAD_IMAGE->value,
            ],
            'Genuine_Product_Verification' => [
                PermissionList::READ_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Color' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Color_Group' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Size' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Size_Group' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Barcode' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Category' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Tag' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Department' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Shift_Close' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Member_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Stock_Position' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'External_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Reserved_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Transit_Inventory' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Product_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Profit_And_Loss_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Consignment_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Stock_Movement_Ledger' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Product_Serial_Number' => [PermissionList::READ_RECORD->value],
            'Stock_Take' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Open_Counter' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Quantity_Sold' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sell_Through' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Product_Ageing' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Sale_Analysis' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Day_Close' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Cash_Movement' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Payment_Type_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Commission' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Voucher' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Booking_Payment' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Member_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Credit_Note' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Activities' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Custom_Report' => [PermissionList::READ_RECORD->value],
            'Sale_Return_Reason' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Payment_Type' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Void_Sale_Reason' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Cash_Movement_Reason' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Sale_Through_Ratio' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Goods_Received_Note' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Stock_Adjustment' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Stock_Transfer' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Stock_Transfer_Reason' => [
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
            'Member' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
            ],
            'Member_Group' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Membership' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Loyalty_Campaign' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Dream_Price' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Complimentary_Setup' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Promotion' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Mystery_Gift' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Vouchers_Configuration' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Cashback' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Gift_Card' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Sale_Target' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Sale_Achieved_Target' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Happy_Hour' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Employee_Sale' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'ecommerce' => [PermissionList::READ_RECORD->value],
            'Warehouse_Manager_Role' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Store_Manager_Role' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Sale_Seasons' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
            ],
            'Batch_Expiry' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'order' => [PermissionList::READ_RECORD->value],
            'order_return' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Draft_Product' => [
                PermissionList::READ_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
            ],
            'External_Login' => [PermissionList::READ_RECORD->value],
            'Banner' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Product_Collection' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
            ],
            'Template' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Attribute' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Template_Attribute' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
            ],
            'Loyalty_Campaign_Configuration' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Rewards' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Location' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Online_Sales_Charges' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
            ],
            'Order_Picking_Lists' => [PermissionList::READ_RECORD->value],
            'Digital_Invoice' => [PermissionList::E_INVOICE_GENERATE->value],
            'User' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Master_Product' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::REMOVE_RECORD->value,
                PermissionList::MASTER_PRODUCT_PURCHASE_COST->value,
                PermissionList::EXPORT_RECORD->value,
                PermissionList::MASTER_PRODUCT_UPLOAD_IMAGE->value,
            ],
            'Email_Template' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Online_Product_Report' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Purchase_Plan' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'External_Purchase_Order' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],

            'External_Purchase_Order_Receive' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Country' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'State' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'City' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
            'Stock_Movement_Summary' => [PermissionList::READ_RECORD->value, PermissionList::EXPORT_RECORD->value],
            'Shipping_Zone' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Dynamic_Menus' => [
                PermissionList::READ_RECORD->value,
                PermissionList::WRITE_RECORD->value,
                PermissionList::MODIFY_RECORD->value,
            ],
            'Genuine_Receipt_Verification' => [
                PermissionList::READ_RECORD->value,
                PermissionList::EXPORT_RECORD->value,
            ],
        ];
    }
}
