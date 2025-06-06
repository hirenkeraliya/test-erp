<?php

declare(strict_types=1);

namespace App\Domains\Location\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LocationImportColumns: string
{
    use PrepareEnumDataMethods;

    case TYPE = 'type';
    case NAME = 'name';
    case CODE = 'code';
    case BRANDS = 'brands';
    case REGISTRATION_NUMBER = 'registration_number';
    case SST_NUMBER = 'sst_number';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case MOBILE = 'mobile';
    case FAX = 'fax';
    case ADDRESS_LINE_1 = 'address_line_1';
    case ADDRESS_LINE_2 = 'address_line_2';
    case CITY = 'city';
    case AREA_CODE = 'area_code';
    case WEBSITE = 'website';
    case SALES_TAX_PERCENTAGE = 'sales_tax_percentage';
    case SALES_RETURN_DAYS_LIMIT = 'sales_return_days_limit';
    case CREDIT_NOTE_EXPIRATION_DAYS = 'credit_note_expiration_days';
    case LOYALTY_POINT_EXPIRATION_DAYS = 'loyalty_point_expiration_days';
    case RECEIPT_FOOTER = 'receipt_footer';
    case DISCLAIMER = 'disclaimer';
    case CASH_OUT_LIMIT_INFO = 'cash_out_limit_info';
    case CASH_OUT_LIMIT_WARNING = 'cash_out_limit_warning';
    case CASH_OUT_LIMIT_RESTRICT = 'cash_out_limit_restrict';
    case PRICE_FALL_DOWN_PERCENTAGE = 'price_fall_down_percentage';
    case OPEN_TIME = 'open_time';
    case CLOSE_TIME = 'close_time';
    case COUNTRY = 'country';
    case STATE = 'state';
}
