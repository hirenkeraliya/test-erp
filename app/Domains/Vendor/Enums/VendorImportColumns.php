<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum VendorImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case CODE = 'code';
    case REGISTRATION_NUMBER = 'registration_number';
    case SST_NUMBER = 'sst_number';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case MOBILE = 'mobile';
    case FAX = 'fax';
    case WEBSITE = 'website';
    case ADDRESS_LINE_1 = 'address_line_1';
    case ADDRESS_LINE_2 = 'address_line_2';
    case CITY = 'city';
    case AREA_CODE = 'area_code';
    case CONSIGNMENT = 'consignment';
    case COMMISSION_PERCENTAGE = 'commission_percentage';
}
