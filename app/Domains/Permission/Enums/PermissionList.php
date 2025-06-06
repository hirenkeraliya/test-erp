<?php

declare(strict_types=1);

namespace App\Domains\Permission\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PermissionList: string
{
    use PrepareEnumDataMethods;

    case READ_RECORD = 'read_record';
    case WRITE_RECORD = 'write_record';
    case MODIFY_RECORD = 'modify_record';
    case REMOVE_RECORD = 'remove_record';
    case IMPORT_RECORDS = 'import_records';
    case EXPORT_RECORD = 'export_record';
    case PRODUCT_FRANCHISE_PRICE_ONE = 'franchise_price_1';
    case PRODUCT_FRANCHISE_PRICE_TWO = 'franchise_price_2';
    case DASHBOARD_OPERATIONAL = 'operational';
    case DASHBOARD_STORE_REVENUE = 'store_revenue';
    case DASHBOARD_BUSINESS = 'business';
    case DASHBOARD_STOCK_OVERVIEW = 'stock_overview';
    case DASHBOARD_SEASONAL = 'seasonal';
    case DASHBOARD_MEMBER = 'member';
    case DASHBOARD_SALE_TARGET = 'sale_target';
    case PRODUCT_PURCHASE_COST = 'purchase_cost';
    case PRODUCT_UPLOAD_IMAGE = 'upload_image';
    case E_INVOICE_GENERATE = 'e_invoice_generate';
    case MASTER_PRODUCT_PURCHASE_COST = 'master_product_purchase_cost';
    case MASTER_PRODUCT_UPLOAD_IMAGE = 'master_product_upload_image';

    public static function getProductPermissionColumns(): array
    {
        return [
            self::PRODUCT_FRANCHISE_PRICE_ONE->value,
            self::PRODUCT_FRANCHISE_PRICE_TWO->value,
            self::PRODUCT_PURCHASE_COST->value,
            self::PRODUCT_UPLOAD_IMAGE->value,
        ];
    }

    public static function getExportPermissionName(string $moduleName): string
    {
        return $moduleName . '_' . self::EXPORT_RECORD->value;
    }

    public static function getReadPermissionName(string $moduleName): string
    {
        return $moduleName . '_' . self::READ_RECORD->value;
    }

    public static function getWritePermissionName(string $moduleName): string
    {
        return $moduleName . '_' . self::WRITE_RECORD->value;
    }

    public static function getModifyPermissionName(string $moduleName): string
    {
        return $moduleName . '_' . self::MODIFY_RECORD->value;
    }

    public static function getRemovePermissionName(string $moduleName): string
    {
        return $moduleName . '_' . self::REMOVE_RECORD->value;
    }
}
