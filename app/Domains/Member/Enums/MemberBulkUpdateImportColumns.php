<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MemberBulkUpdateImportColumns: string
{
    use PrepareEnumDataMethods;

    case TYPE = 'type';
    case TITLE = 'title';
    case RACE = 'race';
    case FIRST_NAME = 'first_name';
    case LAST_NAME = 'last_name';
    case GENDER = 'gender';
    case DATE_OF_BIRTH = 'date_of_birth';
    case MOBILE_NUMBER = 'mobile_number';
    case EMAIL = 'email';
    case COMPANY_NAME = 'company_name';
    case COMPANY_REGISTRATION_NUMBER = 'company_registration_number';
    case COMPANY_TAX_NUMBER = 'company_tax_number';
    case COMPANY_ADDRESS = 'company_address';
    case COMPANY_PHONE = 'company_phone';
    case PIC_NAME = 'pic_name';
    case PIC_CONTACT = 'pic_contact';
    case CREATED_LOCATION = 'created_location';
    case LAST_PURCHASE_DATE = 'last_purchase_date';
    case REGISTERED_DATE = 'registered_date';
}
