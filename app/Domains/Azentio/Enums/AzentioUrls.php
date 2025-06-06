<?php

declare(strict_types=1);

namespace App\Domains\Azentio\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AzentioUrls: string
{
    use PrepareEnumDataMethods;

    case LOGIN_URL = '/oneerpauth/api/login';
    case GET_API_URL = '/oneerpreport/api/getapi';
}
