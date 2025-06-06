<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Enums;

use App\Domains\AutomatedNotification\Imports\ImportAutomatedNotificationProducts;
use App\Domains\Cashier\Imports\ImportCashier;
use App\Domains\Cashier\Imports\ImportCashierBulkUpdate;
use App\Domains\CashierGroup\Imports\ImportCashierGroup;
use App\Domains\CashierGroup\Imports\ImportCashierGroupBulkUpdate;
use App\Domains\Category\Imports\ImportCategory;
use App\Domains\Category\Imports\ImportCategoryBulkUpdate;
use App\Domains\Color\Imports\ImportColor;
use App\Domains\Color\Imports\ImportColorBulkUpdate;
use App\Domains\ColorGroup\Imports\ImportColorGroup;
use App\Domains\ColorGroup\Imports\ImportColorGroupBulkUpdate;
use App\Domains\Counter\Imports\ImportCounter;
use App\Domains\Counter\Imports\ImportCounterBulkUpdate;
use App\Domains\DreamPrice\Imports\ImportDreamPrice;
use App\Domains\Employee\Imports\ImportEmployee;
use App\Domains\Employee\Imports\ImportEmployeesBulkUpdate;
use App\Domains\GoodsReceivedNote\Imports\ImportGoodsReceivedNoteProduct;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\Location\Imports\ImportLocation;
use App\Domains\Location\Imports\ImportLocationBulkUpdate;
use App\Domains\Member\Imports\ImportAddLoyaltyPoints;
use App\Domains\Member\Imports\ImportMember;
use App\Domains\Member\Imports\ImportMemberAddress;
use App\Domains\Member\Imports\ImportMembersBulkUpdate;
use App\Domains\Member\Imports\ImportUpdateLoyaltyPoints;
use App\Domains\MemberGroup\Imports\ImportMemberGroupMembers;
use App\Domains\MemberGroup\Imports\ImportMemberGroupProducts;
use App\Domains\PaymentType\Imports\ImportPaymentType;
use App\Domains\PaymentType\Imports\ImportPaymentTypeBulkUpdate;
use App\Domains\Product\Imports\ImportBulkProductMerge;
use App\Domains\Product\Imports\ImportProduct;
use App\Domains\Product\Imports\ImportProductPriceUpdate;
use App\Domains\Product\Imports\ImportProductUpdate;
use App\Domains\Product\Imports\ImportSetProductBoxUnits;
use App\Domains\Product\Imports\ImportSetProductLoyaltyPoints;
use App\Domains\Promoter\Imports\ImportPromoter;
use App\Domains\Promoter\Imports\ImportPromoterBulkUpdate;
use App\Domains\Region\Imports\ImportRegion;
use App\Domains\Region\Imports\ImportRegionBulkUpdate;
use App\Domains\Size\Imports\ImportSize;
use App\Domains\Size\Imports\ImportSizeBulkUpdate;
use App\Domains\SizeGroup\Imports\ImportSizeGroup;
use App\Domains\SizeGroup\Imports\ImportSizeGroupBulkUpdate;
use App\Domains\StockAdjustment\Imports\ImportStockAdjustmentStiProduct;
use App\Domains\StockAdjustment\Imports\ImportStockAdjustmentStoProduct;
use App\Domains\StockTake\Imports\ImportStockTakes;
use App\Domains\StoreManager\Imports\ImportStoreManager;
use App\Domains\StoreManager\Imports\ImportStoreManagerBulkUpdate;
use App\Domains\Vendor\Imports\ImportVendor;
use App\Domains\Vendor\Imports\ImportVendorBulkUpdate;
use App\Http\Traits\PrepareEnumDataMethods;

enum ImportTypes: int
{
    use PrepareEnumDataMethods;

    case PRODUCTS = 1;
    case MEMBERS = 2;
    case MEMBERS_BULK_UPDATE = 3;
    case PRODUCT_PRICE_BULK_UPDATE = 4;
    case EMPLOYEES = 5;
    case PAYMENT_TYPES = 6;
    case COUNTERS = 7;
    case PRODUCT_BULK_UPDATE = 9;
    case DREAM_PRICE = 10;
    case UPDATE_MEMBER_LOYALTY_POINTS = 11;
    case ADD_MEMBER_LOYALTY_POINTS = 12;
    case PROMOTERS = 13;
    case STOCK_TAKES = 14;
    case REGIONS = 15;
    case COLOR_GROUPS = 16;
    case VENDORS = 17;
    case SIZE_GROUPS = 18;
    case SIZES = 19;
    case COLORS = 20;
    case CASHIERS = 21;
    case STORE_MANAGERS = 22;
    case EMPLOYEES_BULK_UPDATE = 23;
    case CATEGORIES = 24;
    case PRODUCT_BULK_IMAGE_UPLOAD = 25;
    case BULK_PRODUCT_MERGE = 26;
    case GOODS_RECEIVE_NOTE = 27;
    case STOCK_ADJUSTMENT_STO = 28;
    case STOCK_ADJUSTMENT_STI = 29;
    case SET_PRODUCT_LOYALTY_POINTS = 30;
    case SET_PRODUCT_BOX_UNITS = 31;
    case PRODUCT_COLLECTION = 32;
    case MEMBER_ADDRESS = 33;
    case PAYMENT_TYPE_BULK_UPDATE = 34;
    case COUNTER_BULK_UPDATE = 35;
    case PROMOTER_BULK_UPDATE = 37;
    case VENDOR_BULK_UPDATE = 38;
    case REGIONS_BULK_UPDATE = 39;
    case COLOR_GROUP_BULK_UPDATE = 40;
    case SIZE_GROUP_BULK_UPDATE = 41;
    case COLOR_BULK_UPDATE = 42;
    case STORE_MANAGER_BULK_UPDATE = 43;
    case SIZE_BULK_UPDATE = 44;
    case CATEGORY_BULK_UPDATE = 45;
    case CASHIER_BULK_UPDATE = 46;
    case CASHIER_GROUPS = 47;
    case CASHIER_GROUPS_BULK_UPDATE = 48;
    case LOCATIONS = 49;
    case LOCATION_BULK_UPDATE = 50;
    case AUTOMATED_NOTIFICATION_PRODUCTS = 51;
    case MEMBER_GROUP_MEMBERS = 52;
    case MEMBER_GROUP_PRODUCTS = 53;
    case MEMBER_GROUP = 54;

    public static function getClassFor(int $importTypeValue): ImportRecordClassInterface
    {
        // Note: Class is dynamic as per the Import types
        if ($importTypeValue === self::MEMBERS_BULK_UPDATE->value) {
            return resolve(ImportMembersBulkUpdate::class);
        }

        if ($importTypeValue === self::PRODUCTS->value) {
            return resolve(ImportProduct::class);
        }

        if ($importTypeValue === self::PRODUCT_PRICE_BULK_UPDATE->value) {
            return resolve(ImportProductPriceUpdate::class);
        }

        if ($importTypeValue === self::EMPLOYEES->value) {
            return resolve(ImportEmployee::class);
        }

        if ($importTypeValue === self::PAYMENT_TYPES->value) {
            return resolve(ImportPaymentType::class);
        }

        if ($importTypeValue === self::COUNTERS->value) {
            return resolve(ImportCounter::class);
        }

        if ($importTypeValue === self::PRODUCT_BULK_UPDATE->value) {
            return resolve(ImportProductUpdate::class);
        }

        if ($importTypeValue === self::DREAM_PRICE->value) {
            return resolve(ImportDreamPrice::class);
        }

        if ($importTypeValue === self::UPDATE_MEMBER_LOYALTY_POINTS->value) {
            return resolve(ImportUpdateLoyaltyPoints::class);
        }

        if ($importTypeValue === self::ADD_MEMBER_LOYALTY_POINTS->value) {
            return resolve(ImportAddLoyaltyPoints::class);
        }

        if ($importTypeValue === self::PROMOTERS->value) {
            return resolve(ImportPromoter::class);
        }

        if ($importTypeValue === self::STOCK_TAKES->value) {
            return resolve(ImportStockTakes::class);
        }

        if ($importTypeValue === self::COLOR_GROUPS->value) {
            return resolve(ImportColorGroup::class);
        }

        if ($importTypeValue === self::REGIONS->value) {
            return resolve(ImportRegion::class);
        }

        if ($importTypeValue === self::VENDORS->value) {
            return resolve(ImportVendor::class);
        }

        if ($importTypeValue === self::SIZE_GROUPS->value) {
            return resolve(ImportSizeGroup::class);
        }

        if ($importTypeValue === self::SIZES->value) {
            return resolve(ImportSize::class);
        }

        if ($importTypeValue === self::SIZE_BULK_UPDATE->value) {
            return resolve(ImportSizeBulkUpdate::class);
        }

        if ($importTypeValue === self::COLORS->value) {
            return resolve(ImportColor::class);
        }

        if ($importTypeValue === self::COLOR_BULK_UPDATE->value) {
            return resolve(ImportColorBulkUpdate::class);
        }

        if ($importTypeValue === self::CASHIERS->value) {
            return resolve(ImportCashier::class);
        }

        if ($importTypeValue === self::CASHIER_BULK_UPDATE->value) {
            return resolve(ImportCashierBulkUpdate::class);
        }

        if ($importTypeValue === self::CASHIER_GROUPS->value) {
            return resolve(ImportCashierGroup::class);
        }

        if ($importTypeValue === self::CASHIER_GROUPS_BULK_UPDATE->value) {
            return resolve(ImportCashierGroupBulkUpdate::class);
        }

        if ($importTypeValue === self::STORE_MANAGERS->value) {
            return resolve(ImportStoreManager::class);
        }

        if ($importTypeValue === self::STORE_MANAGER_BULK_UPDATE->value) {
            return resolve(ImportStoreManagerBulkUpdate::class);
        }

        if ($importTypeValue === self::EMPLOYEES_BULK_UPDATE->value) {
            return resolve(ImportEmployeesBulkUpdate::class);
        }

        if ($importTypeValue === self::PAYMENT_TYPE_BULK_UPDATE->value) {
            return resolve(ImportPaymentTypeBulkUpdate::class);
        }

        if ($importTypeValue === self::COUNTER_BULK_UPDATE->value) {
            return resolve(ImportCounterBulkUpdate::class);
        }

        if ($importTypeValue === self::COLOR_GROUP_BULK_UPDATE->value) {
            return resolve(ImportColorGroupBulkUpdate::class);
        }

        if ($importTypeValue === self::PROMOTER_BULK_UPDATE->value) {
            return resolve(ImportPromoterBulkUpdate::class);
        }

        if ($importTypeValue === self::VENDOR_BULK_UPDATE->value) {
            return resolve(ImportVendorBulkUpdate::class);
        }

        if ($importTypeValue === self::REGIONS_BULK_UPDATE->value) {
            return resolve(ImportRegionBulkUpdate::class);
        }

        if ($importTypeValue === self::SIZE_GROUP_BULK_UPDATE->value) {
            return resolve(ImportSizeGroupBulkUpdate::class);
        }

        if ($importTypeValue === self::CATEGORIES->value) {
            return resolve(ImportCategory::class);
        }

        if ($importTypeValue === self::CATEGORY_BULK_UPDATE->value) {
            return resolve(ImportCategoryBulkUpdate::class);
        }

        if ($importTypeValue === self::BULK_PRODUCT_MERGE->value) {
            return resolve(ImportBulkProductMerge::class);
        }

        if ($importTypeValue === self::SET_PRODUCT_LOYALTY_POINTS->value) {
            return resolve(ImportSetProductLoyaltyPoints::class);
        }

        if ($importTypeValue === self::GOODS_RECEIVE_NOTE->value) {
            return resolve(ImportGoodsReceivedNoteProduct::class);
        }

        if ($importTypeValue === self::STOCK_ADJUSTMENT_STO->value) {
            return resolve(ImportStockAdjustmentStoProduct::class);
        }

        if ($importTypeValue === self::STOCK_ADJUSTMENT_STI->value) {
            return resolve(ImportStockAdjustmentStiProduct::class);
        }

        if ($importTypeValue === self::SET_PRODUCT_BOX_UNITS->value) {
            return resolve(ImportSetProductBoxUnits::class);
        }

        if ($importTypeValue === self::MEMBER_ADDRESS->value) {
            return resolve(ImportMemberAddress::class);
        }

        if ($importTypeValue === self::LOCATIONS->value) {
            return resolve(ImportLocation::class);
        }

        if ($importTypeValue === self::LOCATION_BULK_UPDATE->value) {
            return resolve(ImportLocationBulkUpdate::class);
        }

        if ($importTypeValue === self::AUTOMATED_NOTIFICATION_PRODUCTS->value) {
            return resolve(ImportAutomatedNotificationProducts::class);
        }

        if ($importTypeValue === self::MEMBER_GROUP_MEMBERS->value) {
            return resolve(ImportMemberGroupMembers::class);
        }

        if ($importTypeValue === self::MEMBER_GROUP_PRODUCTS->value) {
            return resolve(ImportMemberGroupProducts::class);
        }

        return resolve(ImportMember::class);
    }
}
