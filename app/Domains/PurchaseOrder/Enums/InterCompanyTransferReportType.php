<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum InterCompanyTransferReportType: int
{
    use PrepareEnumDataMethods;

    case SUMMARY_BY_ARTICLE = 1;
    case BY_DOCUMENT = 2;
    case BY_DETAILS = 3;
    case BY_SUMMARY_UPC = 4;
}
