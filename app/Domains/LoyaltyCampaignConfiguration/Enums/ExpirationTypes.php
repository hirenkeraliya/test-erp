<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExpirationTypes: int
{
    use PrepareEnumDataMethods;

    case NEVER = 1;
    case THREE_MONTHS = 2;
    case SIX_MONTHS = 3;
    case TWELVE_MONTHS = 4;
    case TWENTY_FOUR_MONTHS = 5;
}
