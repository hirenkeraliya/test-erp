<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Enums;

use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\Inventory\Exports\ExportInventories;
use App\Domains\Member\Exports\ExportMember;
use App\Domains\Product\Exports\ExportBoxProduct;
use App\Domains\Product\Exports\ExportLoyaltyPointProduct;
use App\Domains\Product\Exports\ExportProduct;
use App\Domains\Product\Exports\ExportProductForImportBulkUpdate;
use App\Domains\ProductAgeingReport\Exports\ExportProductAgeing;
use App\Domains\ProductAgeingReport\Exports\ExportProductAgeingByArticleNumber;
use App\Domains\ProductAgeingReport\Exports\ExportProductAgeingByMonthAndYear;
use App\Domains\ProductAgeingReport\Exports\ExportProductAgeingByUpc;
use App\Http\Traits\PrepareEnumDataMethods;

enum ExportRecordTypes: int
{
    use PrepareEnumDataMethods;

    case SALES_COLLECTION_BY_DATE_REPORT = 1;
    case SALES_COLLECTION_BY_RECEIPT_REPORT = 2;
    case SALES_COLLECTION_BY_CASHIER_REPORT = 3;
    case SALES_COLLECTION_BY_COUNTER_REPORT = 4;
    case SALES_COLLECTION_BY_TIME_REPORT = 5;
    case SALES_COLLECTION_BY_COUNTER_AND_CASHIER_REPORT = 6;
    case SALES_COLLECTION_BY_SUMMARY_REPORT = 7;
    case STOCK_MOVEMENT_REPORT = 8;
    case SALE_EXCHANGE_REPORT = 9;
    case VOID_SALE_REPORT = 10;
    case GENERAL_SALES_BY_PRODUCT_REPORT = 11;
    case GENERAL_SALES_BY_PROMOTER_REPORT = 12;
    case GENERAL_SALES_BY_COLOR_AND_SIZE_REPORT = 13;
    case GENERAL_SALES_BY_ITEM_AND_RECEIPT_REPORT = 14;
    case GENERAL_SALES_BY_RECEIPT_AND_ITEM_REPORT = 15;
    case TOP_TWENTY_CATEGORIES_REPORT = 16;
    case WORST_TWENTY_CATEGORIES_REPORT = 17;
    case STOCK_CARD_REPORT = 18;
    case CASH_MOVEMENT_REPORT = 19;
    case STOCK_SUMMARY_REQUEST_ORDER_BY_DEPARTMENT_REPORT = 20;
    case STOCK_SUMMARY_REQUEST_ORDER_BY_COLOR_AND_SIZE_REPORT = 21;
    case STOCK_SUMMARY_TRANSFER_ORDER_BY_DEPARTMENT_REPORT = 22;
    case STOCK_SUMMARY_TRANSFER_ORDER_BY_COLOR_AND_SIZE_REPORT = 23;
    case PROMOTER_COMMISSION_REPORT = 24;
    case PROMOTER_COMMISSION_BY_ITEM_REPORT = 25;
    case PROMOTER_COMMISSION_BY_DEPARTMENT_REPORT = 26;
    case PROMOTER_COMMISSION_BY_BRAND_REPORT = 27;
    case SALES_BY_PROMOTER_BY_DEPARTMENT_REPORT = 28;
    case SALES_BY_PROMOTER_BY_BRAND_REPORT = 29;
    case MEMBERS = 30;
    case PRODUCTS = 31;
    case BARCODE = 32;
    case INVENTORIES = 33;
    case PRODUCT_AGEING = 34;
    case PRODUCT_AGEING_BY_MONTH_AND_YEAR = 35;
    case PRODUCT_LOYALTY_POINTS = 36;
    case PRODUCT_BOXES = 37;
    case PRODUCT_AGEING_BY_ARTICLE_NUMBER = 38;
    case PRODUCT_AGEING_BY_UPC = 39;
    case PRODUCT_EXPORT_FOR_IMPORT_BULK_UPDATE = 40;

    // Note: Class is dynamic as per the Export types
    public static function getClassFor(int $exportTypeValue): ExportRecordClassInterface
    {
        $exportRecordClasses = [
            self::INVENTORIES->value => ExportInventories::class,
            self::PRODUCTS->value => ExportProduct::class,
            self::MEMBERS->value => ExportMember::class,
            self::PRODUCT_AGEING->value => ExportProductAgeing::class,
            self::PRODUCT_AGEING_BY_MONTH_AND_YEAR->value => ExportProductAgeingByMonthAndYear::class,
            self::PRODUCT_LOYALTY_POINTS->value => ExportLoyaltyPointProduct::class,
            self::PRODUCT_BOXES->value => ExportBoxProduct::class,
            self::PRODUCT_AGEING_BY_ARTICLE_NUMBER->value => ExportProductAgeingByArticleNumber::class,
            self::PRODUCT_AGEING_BY_UPC->value => ExportProductAgeingByUpc::class,
            self::PRODUCT_EXPORT_FOR_IMPORT_BULK_UPDATE->value => ExportProductForImportBulkUpdate::class,
        ];

        return resolve($exportRecordClasses[$exportTypeValue]);
    }

    public static function getEmailTypeFor(int $exportTypeValue): EmailTypes
    {
        return EmailTypes::EXPORT_MEMBERS;
    }
}
