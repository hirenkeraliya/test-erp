<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case DESCRIPTION = 'description';
    case CODE = 'code';
    case UNIT_OF_MEASURE = 'unit_of_measure';
    case SEASON = 'season';
    case DEPARTMENT = 'department';
    case SUB_DEPARTMENT = 'sub_department';
    case COLOR = 'color';
    case SIZE = 'size';
    case STYLE = 'style';
    case IS_TEMPORARILY_UNAVAILABLE = 'is_temporarily_unavailable';
    case UPC = 'upc';
    case EAN = 'ean';
    case CUSTOM_SKU = 'custom_sku';
    case MANUFACTURER_SKU = 'manufacturer_sku';
    case BRAND = 'brand';
    case TYPE_ID = 'type_id';
    case RETAIL_PRICE = 'retail_price';
    case FRANCHISE_PRICE_1 = 'franchise_price_1';
    case FRANCHISE_PRICE_2 = 'franchise_price_2';
    case FRANCHISE_PRICE_3 = 'franchise_price_3';
    case WHOLESALE_PRICE = 'wholesale_price';
    case COMPANY_OR_TENDER_PRICE = 'company_or_tender_price';
    case BRANCH_PRICE = 'branch_price';
    case MINIMUM_PRICE = 'minimum_price';
    case ORIGINAL_CAPITAL_PRICE = 'original_capital_price';
    case CAPITAL_PRICE = 'capital_price';
    case STAFF_PRICE = 'staff_price';
    case PURCHASE_COST = 'purchase_cost';
    case ONLINE_PRICE = 'online_price';
    case ARTICLE_NUMBER = 'article_number';
    case CATEGORY_NAME = 'category_name';
    case SUBCATEGORY_NAME = 'subcategory_name';
    case SUBSUBCATEGORY_NAME = 'subsubcategory_name';
    case HAS_BATCH = 'has_batch';
    case IS_NON_SELLING_ITEM = 'is_non_selling_item';
    case IS_NON_INVENTORY = 'is_non_inventory';
    case TAGS = 'tags';
    case IS_AVAILABLE_IN_POS = 'is_available_in_pos';
    case IS_AVAILABLE_IN_ECOMMERCE = 'is_available_in_ecommerce';
    case IS_SOLD_AS_SINGLE_ITEM = 'is_sold_as_single_item';
    case SELL_ITEM_VIA_DERIVATIVE = 'sell_item_via_derivative';
    case ORIGINAL_CREATED_AT = 'original_created_at';
    case VENDOR = 'vendor';
    case SALE_CHANNELS = 'sale_channels';
    case VERIFICATION_QR_CODE = 'verification_qr_code';
}
