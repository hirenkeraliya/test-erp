<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MemberImportColumns: string
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
    case CREATED_LOCATION = 'created_location';
    case NOTES = 'notes';
    case LOYALTY_POINTS = 'loyalty_points';
    case CARD_NUMBER = 'card_number';
}
