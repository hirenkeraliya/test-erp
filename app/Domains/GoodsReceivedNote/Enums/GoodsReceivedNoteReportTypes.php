<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GoodsReceivedNoteReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_SUMMARY = 1;
    case BY_DETAILS = 2;
    case BY_DOCUMENT = 3;
}
