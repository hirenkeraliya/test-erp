<?php

declare(strict_types=1);

namespace App\Domains\Employee\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum EmployeeBulkUpdateImportColumns: string
{
    use PrepareEnumDataMethods;

    case FIRST_NAME = 'first_name';
    case LAST_NAME = 'last_name';
    case EMAIL = 'email';
    case MOBILE_NUMBER = 'mobile_number';
    case HOME_CONTACT = 'home_contact';
    case ADDRESS_LINE_1 = 'address_line_1';
    case ADDRESS_LINE_2 = 'address_line_2';
    case CITY = 'city';
    case AREA_CODE = 'area_code';
    case DATE_OF_JOINING = 'date_of_joining';
    case PRIMARY_CONTACT_NAME = 'primary_contact_name';
    case PRIMARY_CONTACT_PHONE = 'primary_contact_phone';
    case DESIGNATION_NAME = 'designation_name';
    case GROUP_NAME = 'group_name';
    case STAFF_ID = 'staff_id';
    case IC_NUMBER = 'ic_number';
    case JOB_TYPE = 'job_type';
}
