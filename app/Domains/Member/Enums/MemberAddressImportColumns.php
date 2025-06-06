<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MemberAddressImportColumns: string
{
    use PrepareEnumDataMethods;

    case FIRST_NAME = 'first_name';
    case MOBILE_NUMBER = 'mobile_number';
    case NAME = 'name';
    case CONTACT_MOBILE_NUMBER = 'contact_mobile_number';
    case CONTACT_EMAIL = 'contact_email';
    case ADDRESS_LINE_1 = 'address_line_1';
    case ADDRESS_LINE_2 = 'address_line_2';
    case CITY = 'city';
    case AREA_CODE = 'area_code';
    case IS_PRIMARY = 'is_primary';
}
