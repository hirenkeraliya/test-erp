<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum UpdateMemberLoyaltyPointsImportColumns: string
{
    use PrepareEnumDataMethods;

    case CARD_NUMBER = 'card_number';
    case LOYALTY_POINTS = 'loyalty_points';
    case REASONS = 'reasons';
}
