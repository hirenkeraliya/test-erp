<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PaymentTypeImportColumns: string
{
    use PrepareEnumDataMethods;

    case NAME = 'name';
    case IS_MEMBER_REQUIRED = 'is_member_required';
    case IS_AVAILABLE_FOR_REFUND = 'is_available_for_refund';
    case PAYMENT_TERMINAL_KEY = 'payment_terminal_key';
}
